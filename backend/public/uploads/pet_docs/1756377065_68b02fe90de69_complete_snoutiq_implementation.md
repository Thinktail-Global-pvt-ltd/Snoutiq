# Complete SnoutIQ AI Assistant Implementation Guide

## 1. AI Assistant Prompt

### System Prompt
```
**Role:** Act as SnoutIQ Assistant, an empathetic guide for Indian pet parents. You are NOT a vet and cannot diagnose or treat. Your role is to comfort worried pet parents and guide them to appropriate care.

**Response Structure:**
1. **Address Emotion First:** Always acknowledge the pet parent's concern ("I understand how worrying this must be" / "It's natural to feel concerned when...")
2. **Safety Priority:** For any symptoms, state: "It's important to have a veterinarian examine [pet_name] for this."
3. **Provide Context:** Add general, educational information to help them understand why vet care is needed.
4. **Natural Guidance:** Weave in the appropriate next step as caring advice, not a sales pitch.

**Safety Rules:**
- Never diagnose, treat, or recommend human medicines
- For symptoms: emotion → vet recommendation → context → guidance
- For emergencies: lead with urgency while staying compassionate

**Output Tags (last line only):**
- `[GENERAL_GUIDANCE]` - informational questions
- `[VIDEO_CONSULT_SUGGESTED]` - non-emergency symptoms  
- `[CLINIC_VISIT_NEEDED]` - physical examination required
- `[EMERGENCY]` - critical situations needing immediate care

**Tone:** Warm, understanding, and naturally helpful - like a knowledgeable friend who cares about both pet and parent.
```

### User Prompt Template
```
{context_section}

**Pet Profile:** {pet_name}, {pet_breed}, {pet_age}, {pet_sex}
**Location:** {user_location}
**Current Question:** {user_question}
```

## 2. Database Schema

```sql
-- Main consultation table
CREATE TABLE pet_consultations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(50) NOT NULL,
    user_id BIGINT NOT NULL, -- Link to existing user table
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Pet Information (from user profile)
    pet_name VARCHAR(100),
    pet_breed VARCHAR(100),
    pet_age VARCHAR(50),
    pet_sex ENUM('Male', 'Female', 'Unknown'),
    user_location VARCHAR(100),
    
    -- Interaction Data
    user_query TEXT NOT NULL,
    gemini_response TEXT NOT NULL,
    clean_response TEXT NOT NULL, -- Response without classification tag
    classification_tag VARCHAR(50),
    conversation_context TEXT, -- Context sent to AI
    
    -- Quality Metrics
    user_feedback TINYINT, -- 1=thumbs up, -1=thumbs down, NULL=no feedback
    response_time_ms INT,
    
    -- Training Data Fields
    vet_validated BOOLEAN DEFAULT FALSE,
    vet_notes TEXT,
    data_quality_score TINYINT DEFAULT 3, -- 1-5 scale
    
    INDEX idx_session (session_id),
    INDEX idx_user (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_classification (classification_tag)
);

-- Session summary table
CREATE TABLE consultation_sessions (
    session_id VARCHAR(50) PRIMARY KEY,
    user_id BIGINT NOT NULL,
    pet_profile JSON, -- Cache pet info for quick access
    conversation_summary TEXT, -- Smart summary of key points
    total_interactions INT DEFAULT 0,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    session_start DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## 3. Backend Implementation

### Core Service Class

```javascript
class SnoutIQAIService {
    
    async processQuery(userId, userQuery, sessionId = null) {
        // 1. Get or create session
        const session = await this.getOrCreateSession(userId, sessionId);
        
        // 2. Get pet profile
        const petProfile = await this.getPetProfile(userId);
        
        // 3. Build context
        const context = await this.buildContext(session.session_id);
        
        // 4. Call AI
        const startTime = Date.now();
        const aiResponse = await this.callGemini(context, petProfile, userQuery);
        const responseTime = Date.now() - startTime;
        
        // 5. Parse response
        const { cleanResponse, classificationTag } = this.parseAIResponse(aiResponse);
        
        // 6. Store interaction
        const consultationId = await this.storeConsultation({
            sessionId: session.session_id,
            userId,
            petProfile,
            userQuery,
            geminiResponse: aiResponse,
            cleanResponse,
            classificationTag,
            conversationContext: context,
            responseTime
        });
        
        // 7. Update session
        await this.updateSession(session.session_id, userQuery, cleanResponse);
        
        return {
            response: cleanResponse,
            classificationTag,
            consultationId,
            sessionId: session.session_id
        };
    }
    
    // Context Management - Two Options
    
    // OPTION 1: Simple Context (Recommended to start)
    async buildContext(sessionId) {
        const recentQueries = await db.query(`
            SELECT user_query, clean_response 
            FROM pet_consultations 
            WHERE session_id = ? 
            ORDER BY timestamp DESC 
            LIMIT 2
        `, [sessionId]);
        
        if (recentQueries.length === 0) return '';
        
        let context = "**Previous conversation:**\n";
        recentQueries.reverse().forEach((q, index) => {
            context += `${index + 1}. User: "${q.user_query}"\n   Assistant: "${q.clean_response.substring(0, 200)}..."\n\n`;
        });
        
        return context;
    }
    
    // OPTION 2: Smart Context Summary  
    async buildSmartContext(sessionId) {
        const allQueries = await db.query(`
            SELECT user_query, clean_response, classification_tag 
            FROM pet_consultations 
            WHERE session_id = ? 
            ORDER BY timestamp ASC
        `, [sessionId]);
        
        if (allQueries.length === 0) return '';
        
        const summary = this.createContextSummary(allQueries);
        
        return `**Conversation Summary:**
**Symptoms mentioned:** ${summary.symptoms.join(', ')}
**Concerns raised:** ${summary.concerns.join(', ')}
**Previous advice given:** ${summary.previousAdvice}
**Last interaction:** ${summary.lastQuery}

`;
    }
    
    createContextSummary(queries) {
        const symptoms = new Set();
        const concerns = new Set();
        let previousAdvice = '';
        let lastQuery = '';
        
        queries.forEach(q => {
            // Extract symptoms (simple keyword matching)
            const symptomKeywords = ['vomiting', 'diarrhea', 'itching', 'limping', 'coughing', 'fever', 'not eating'];
            symptomKeywords.forEach(symptom => {
                if (q.user_query.toLowerCase().includes(symptom)) {
                    symptoms.add(symptom);
                }
            });
            
            // Extract concerns
            const concernKeywords = ['urgent', 'serious', 'worried', 'scared', 'emergency'];
            concernKeywords.forEach(concern => {
                if (q.user_query.toLowerCase().includes(concern)) {
                    concerns.add(concern);
                }
            });
            
            // Get classification pattern
            if (q.classification_tag === 'VIDEO_CONSULT_SUGGESTED') {
                previousAdvice = 'Video consultation recommended';
            } else if (q.classification_tag === 'CLINIC_VISIT_NEEDED') {
                previousAdvice = 'Clinic visit recommended';
            }
            
            lastQuery = q.user_query;
        });
        
        return {
            symptoms: Array.from(symptoms),
            concerns: Array.from(concerns),
            previousAdvice,
            lastQuery
        };
    }
    
    async callGemini(context, petProfile, userQuery) {
        const prompt = `${context}

**Pet Profile:** ${petProfile.name}, ${petProfile.breed}, ${petProfile.age}, ${petProfile.sex}
**Location:** ${petProfile.location}
**Current Question:** ${userQuery}`;
        
        // Add your Gemini API call here
        return await geminiAPI.generateContent(systemPrompt + '\n\n' + prompt);
    }
    
    parseAIResponse(response) {
        const tagMatch = response.match(/\[([A-Z_]+)\]$/);
        const classificationTag = tagMatch ? tagMatch[1] : null;
        const cleanResponse = response.replace(/\[([A-Z_]+)\]$/, '').trim();
        
        return { cleanResponse, classificationTag };
    }
    
    async storeConsultation(data) {
        const result = await db.query(`
            INSERT INTO pet_consultations 
            (session_id, user_id, pet_name, pet_breed, pet_age, pet_sex, user_location,
             user_query, gemini_response, clean_response, classification_tag, 
             conversation_context, response_time_ms)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, [
            data.sessionId, data.userId, data.petProfile.name, data.petProfile.breed,
            data.petProfile.age, data.petProfile.sex, data.petProfile.location,
            data.userQuery, data.geminiResponse, data.cleanResponse, 
            data.classificationTag, data.conversationContext, data.responseTime
        ]);
        
        return result.insertId;
    }
    
    async recordFeedback(consultationId, feedback) {
        await db.query(
            'UPDATE pet_consultations SET user_feedback = ? WHERE id = ?',
            [feedback, consultationId]
        );
    }
    
    async getOrCreateSession(userId, sessionId) {
        if (sessionId) {
            const existing = await db.query(
                'SELECT * FROM consultation_sessions WHERE session_id = ?',
                [sessionId]
            );
            if (existing.length > 0) return existing[0];
        }
        
        // Create new session
        const newSessionId = `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const petProfile = await this.getPetProfile(userId);
        
        await db.query(`
            INSERT INTO consultation_sessions 
            (session_id, user_id, pet_profile) 
            VALUES (?, ?, ?)
        `, [newSessionId, userId, JSON.stringify(petProfile)]);
        
        return { session_id: newSessionId, user_id: userId };
    }
}
```

## 4. Frontend Implementation

### API Integration

```javascript
class SnoutIQChatInterface {
    constructor() {
        this.sessionId = null;
        this.currentConsultationId = null;
    }
    
    async sendMessage(userQuery) {
        try {
            const response = await fetch('/api/ai-consultation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    query: userQuery,
                    sessionId: this.sessionId
                })
            });
            
            const data = await response.json();
            
            // Store session info
            this.sessionId = data.sessionId;
            this.currentConsultationId = data.consultationId;
            
            // Display AI response
            this.displayMessage(data.response, 'assistant');
            
            // Show appropriate action button
            this.showActionButton(data.classificationTag);
            
            return data;
        } catch (error) {
            console.error('AI consultation error:', error);
            this.displayMessage('Sorry, I encountered an error. Please try again.', 'error');
        }
    }
    
    showActionButton(classificationTag) {
        // Remove existing buttons
        document.querySelectorAll('.ai-action-button').forEach(btn => btn.remove());
        
        let buttonHtml = '';
        
        switch(classificationTag) {
            case 'VIDEO_CONSULT_SUGGESTED':
                buttonHtml = `
                    <button class="ai-action-button video-consult-btn" onclick="bookVideoConsult()">
                        📹 Schedule Video Consultation
                    </button>`;
                break;
                
            case 'CLINIC_VISIT_NEEDED':
                buttonHtml = `
                    <button class="ai-action-button clinic-visit-btn" onclick="bookClinicVisit()">
                        🏥 Book Clinic Appointment
                    </button>`;
                break;
                
            case 'EMERGENCY':
                buttonHtml = `
                    <button class="ai-action-button emergency-btn" onclick="findEmergencyVet()">
                        🚨 Find Emergency Vet Now
                    </button>`;
                break;
                
            case 'GENERAL_GUIDANCE':
                // No action button needed for general info
                break;
        }
        
        if (buttonHtml) {
            const chatContainer = document.getElementById('chat-container');
            chatContainer.insertAdjacentHTML('beforeend', buttonHtml);
        }
        
        // Show feedback buttons
        this.showFeedbackButtons();
    }
    
    showFeedbackButtons() {
        const feedbackHtml = `
            <div class="feedback-buttons">
                <button class="feedback-btn thumbs-up" onclick="recordFeedback(1)">👍</button>
                <button class="feedback-btn thumbs-down" onclick="recordFeedback(-1)">👎</button>
            </div>`;
        
        const chatContainer = document.getElementById('chat-container');
        chatContainer.insertAdjacentHTML('beforeend', feedbackHtml);
    }
    
    async recordFeedback(feedback) {
        if (!this.currentConsultationId) return;
        
        try {
            await fetch('/api/feedback', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    consultationId: this.currentConsultationId,
                    feedback: feedback
                })
            });
            
            // Hide feedback buttons after submission
            document.querySelector('.feedback-buttons').style.display = 'none';
            
        } catch (error) {
            console.error('Feedback error:', error);
        }
    }
}

// Action button handlers
function bookVideoConsult() {
    window.location.href = '/book-video-consultation';
}

function bookClinicVisit() {
    window.location.href = '/book-clinic-visit';
}

function findEmergencyVet() {
    window.location.href = '/emergency-vets';
}
```

## 5. API Endpoints

```javascript
// Main consultation endpoint
app.post('/api/ai-consultation', async (req, res) => {
    try {
        const { query, sessionId } = req.body;
        const userId = req.user.id; // From authentication middleware
        
        const aiService = new SnoutIQAIService();
        const result = await aiService.processQuery(userId, query, sessionId);
        
        res.json(result);
    } catch (error) {
        res.status(500).json({ error: 'Internal server error' });
    }
});

// Feedback endpoint
app.post('/api/feedback', async (req, res) => {
    try {
        const { consultationId, feedback } = req.body;
        
        const aiService = new SnoutIQAIService();
        await aiService.recordFeedback(consultationId, feedback);
        
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ error: 'Failed to record feedback' });
    }
});
```

## 6. Configuration Settings

```javascript
// config.js
const CONFIG = {
    AI_SETTINGS: {
        CONTEXT_TYPE: 'simple', // 'simple' or 'smart'
        MAX_CONTEXT_QUERIES: 2,  // For simple context
        SESSION_TIMEOUT_MINUTES: 60,
        MAX_TOKENS: 8000
    },
    
    GEMINI_API: {
        API_KEY: process.env.GEMINI_API_KEY,
        MODEL: 'gemini-pro',
        TEMPERATURE: 0.7
    },
    
    DATABASE: {
        DATA_RETENTION_DAYS: 730, // 2 years for training data
        BACKUP_FREQUENCY: 'daily'
    }
};
```

## 7. Deployment Checklist

### Week 1: Basic Implementation
- [ ] Database schema created
- [ ] Basic AI service implemented with simple context
- [ ] Frontend chat interface with button logic
- [ ] API endpoints functional
- [ ] Basic logging and monitoring

### Week 2: Enhancement
- [ ] User feedback collection working
- [ ] Session management robust
- [ ] Error handling comprehensive
- [ ] Performance monitoring in place

### Month 1: Advanced Features
- [ ] Smart context summary option
- [ ] Analytics dashboard
- [ ] Data export functionality
- [ ] Quality scoring system

## 8. Monitoring & Analytics

```sql
-- Key metrics queries
-- Daily consultation volume
SELECT DATE(timestamp) as date, COUNT(*) as consultations
FROM pet_consultations 
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(timestamp);

-- Classification distribution
SELECT classification_tag, COUNT(*) as count
FROM pet_consultations 
WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY classification_tag;

-- User satisfaction
SELECT 
    AVG(CASE WHEN user_feedback = 1 THEN 1.0 ELSE 0.0 END) as positive_rate,
    COUNT(*) as total_feedback
FROM pet_consultations 
WHERE user_feedback IS NOT NULL;
```

## 9. Important Notes

1. **Pet Profile Integration**: Since you already collect pet info at signup, ensure it's properly linked to user sessions
2. **Context Strategy**: Start with simple context, upgrade to smart summary later
3. **Data Quality**: Every interaction is valuable training data - store everything
4. **Privacy**: No PII in training exports, anonymize sensitive data
5. **Performance**: Monitor response times, optimize context building
6. **Scalability**: Consider caching pet profiles, session data

This implementation gives you a complete, production-ready AI consultation system with session memory and comprehensive data collection for future model training.