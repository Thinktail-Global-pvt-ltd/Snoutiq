#!/usr/bin/env node

/**
 * Small helper to auto-join the Laravel call-page for a given call session.
 *
 * Usage:
 *    node scripts/join-agora-call.js <sessionId> [--base http://127.0.0.1:8000] [--role host] [--duration 60000]
 *
 * Requirements:
 *    - `npm install --save-dev playwright`
 *    - `npx playwright install chromium`
 */

const { chromium } = require('playwright');

const DEFAULT_BASE = 'http://127.0.0.1:8000';

function parseArgs(argv) {
  const args = { _: [] };

  for (let i = 2; i < argv.length; i += 1) {
    const current = argv[i];
    if (current.startsWith('--')) {
      const [key, value] = current.split('=');
      const next = value ?? argv[i + 1];
      args[key.slice(2)] = next;
      if (!value) {
        i += 1;
      }
    } else {
      args._.push(current);
    }
  }

  return args;
}

async function main() {
  const args = parseArgs(process.argv);
  const sessionId = args._[0];

  if (!sessionId) {
    console.error('Usage: node scripts/join-agora-call.js <sessionId> [--base http://127.0.0.1:8000]');
    process.exit(1);
  }

  const baseUrl = args.base || DEFAULT_BASE;
  const role = args.role || 'host';
  const waitMs = Number(args.duration ?? 60000);

  console.log(`Fetching call session ${sessionId} from ${baseUrl}...`);
  const response = await fetch(`${baseUrl}/api/call/${sessionId}`);
  if (!response.ok) {
    throw new Error(`Failed to fetch session ${sessionId}: ${response.status} ${response.statusText}`);
  }

  const session = await response.json();
  const channel = session.channel_name;

  if (!channel) {
    throw new Error('Session does not have a channel_name yet. Run create/accept/start first.');
  }

  const doctorId = session.doctor_id || 501;
  const patientId = session.patient_id || 101;
  const callIdentifier = session.call_identifier || `call_${session.session_id || session.id}`;
  const joinUrl = `${baseUrl}/call-page/${channel}?callId=${encodeURIComponent(
    callIdentifier
  )}&doctorId=${doctorId}&patientId=${patientId}&role=${role}`;

  console.log(`Joining call via ${joinUrl}`);

  const browser = await chromium.launch({
    headless: true,
    args: ['--use-fake-ui-for-media-stream', '--use-fake-device-for-media-stream'],
  });

  const context = await browser.newContext({
    permissions: ['camera', 'microphone'],
  });

  const page = await context.newPage();
  await page.goto(joinUrl, { waitUntil: 'domcontentloaded' });

  // click join button if present
  const joinButton = await page.$('#btn-join');
  if (joinButton) {
    console.log('Clicking "Join" button...');
    await joinButton.click();
  } else {
    console.warn('Join button not found - maybe already connected?');
  }

  console.log(`Staying connected for ${waitMs / 1000}s so Agora sees an active participant...`);
  await page.waitForTimeout(waitMs);

  console.log('Leaving call');
  await page.close();
  await context.close();
  await browser.close();
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
