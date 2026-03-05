import { useState, useRef, useEffect } from "react";
import { GoogleGenAI } from "@google/genai";
import { Send, Bot, User, AlertTriangle, Loader2 } from "lucide-react";
import Markdown from "react-markdown";

export function SymptomCheckerChat() {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!input.trim() || isLoading) return;

    const userMessage = input.trim();
    setInput("");
    setMessages((prev) => [...prev, { role: "user", text: userMessage }]);
    setIsLoading(true);

    try {
      const apiKey = import.meta.env.VITE_GEMINI_API_KEY;
      if (!apiKey) {
        throw new Error("Missing VITE_GEMINI_API_KEY");
      }

      const ai = new GoogleGenAI({
        apiKey,
      });

      const conversationHistory = messages
        .map((m) => `${m.role === "user" ? "Pet Parent" : "AI"}: ${m.text}`)
        .join("\n");

      const prompt = `${conversationHistory}\nPet Parent: ${userMessage}\nAI:`;

      const response = await ai.models.generateContent({
        model: "gemini-2.5-flash",
        contents: prompt,
        config: {
          systemInstruction: `You are SnoutIQ's AI Pet Triage Assistant. Your role is to help pet parents determine if their pet's symptoms are an EMERGENCY or NON-EMERGENCY and provide immediate next steps. 
          RULES: 
          1. DO NOT provide a medical diagnosis under any circumstances. 
          2. State clearly if it is an **[EMERGENCY]** or **[NON-EMERGENCY]**. 
          3. Provide 2-3 actionable next steps. 
          4. ALWAYS end your response with: "For personalized recommendations and to consult with a verified vet, please download the SnoutIQ app."`,
        },
      });

      setMessages((prev) => [
        ...prev,
        {
          role: "ai",
          text: response.text || "Sorry, I encountered an error.",
        },
      ]);
    } catch (error) {
      console.error("Error calling Gemini API:", error);
      setMessages((prev) => [
        ...prev,
        {
          role: "ai",
          text: "Sorry, I am having trouble connecting right now. If this is an emergency, please contact a vet immediately.",
        },
      ]);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden flex flex-col h-[600px] shadow-2xl">
      <div className="bg-brand/10 border-b border-brand/20 p-4 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="bg-brand p-2 rounded-full">
            <Bot className="w-6 h-6 text-slate-900" />
          </div>
          <div>
            <h3 className="text-slate-900 font-bold text-lg">
              AI Symptom Checker
            </h3>
            <p className="text-brand text-sm flex items-center gap-1">
              <AlertTriangle className="w-3 h-3" /> Triage only. Not a diagnosis.
            </p>
          </div>
        </div>
      </div>

      <div className="flex-1 overflow-y-auto p-4 space-y-6">
        {messages.length === 0 && (
          <div className="text-center text-slate-600 mt-10">
            <Bot className="w-12 h-12 mx-auto mb-4 opacity-50 text-brand" />
            <p className="text-lg text-slate-900 mb-2">
              Describe your pet&apos;s symptoms below.
            </p>
            <p className="text-sm">
              Example: &quot;My 3-year-old Golden Retriever has been vomiting
              yellow foam since morning.&quot;
            </p>
          </div>
        )}

        {messages.map((msg, idx) => (
          <div
            key={idx}
            className={`flex gap-3 ${
              msg.role === "user" ? "justify-end" : "justify-start"
            }`}
          >
            {msg.role === "ai" && (
              <div className="w-8 h-8 rounded-full bg-brand/20 flex items-center justify-center flex-shrink-0 mt-1">
                <Bot className="w-5 h-5 text-brand" />
              </div>
            )}

            <div
              className={`max-w-[85%] rounded-2xl p-4 ${
                msg.role === "user"
                  ? "bg-brand text-slate-900 rounded-tr-none"
                  : "bg-slate-100 text-slate-800 rounded-tl-none border border-slate-200"
              }`}
            >
              {msg.role === "user" ? (
                <p className="whitespace-pre-wrap">{msg.text}</p>
              ) : (
                <div className="prose prose-invert prose-sm max-w-none prose-p:leading-relaxed prose-a:text-brand">
                  <Markdown>{msg.text}</Markdown>
                </div>
              )}
            </div>

            {msg.role === "user" && (
              <div className="w-8 h-8 rounded-full bg-brand flex items-center justify-center flex-shrink-0 mt-1">
                <User className="w-5 h-5 text-slate-900" />
              </div>
            )}
          </div>
        ))}

        {isLoading && (
          <div className="flex gap-3 justify-start">
            <div className="w-8 h-8 rounded-full bg-brand/20 flex items-center justify-center flex-shrink-0 mt-1">
              <Bot className="w-5 h-5 text-brand" />
            </div>
            <div className="bg-slate-100 text-slate-800 rounded-2xl rounded-tl-none border border-slate-200 p-4 flex items-center gap-2">
              <Loader2 className="w-4 h-4 animate-spin text-brand" />
              <span className="text-sm">Analyzing symptoms...</span>
            </div>
          </div>
        )}

        <div ref={messagesEndRef} />
      </div>

      <div className="p-4 border-t border-slate-200 bg-white">
        <form onSubmit={handleSubmit} className="flex gap-2">
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder="Describe the symptoms..."
            className="flex-1 bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:outline-none focus:border-brand transition-colors"
            disabled={isLoading}
          />
          <button
            type="submit"
            disabled={isLoading || !input.trim()}
            className="bg-brand text-slate-900 p-3 rounded-xl hover:bg-brand-hover transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
          >
            <Send className="w-5 h-5" />
          </button>
        </form>
      </div>
    </div>
  );
}
