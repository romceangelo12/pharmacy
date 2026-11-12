import fs from 'fs';
import path from 'path';

const logDir = 'C:/Users/JVGH-PHARMA-LT006/.gemini/antigravity/brain/d2f62483-4fa4-4010-9b0f-6de399df5c55/.system_generated/logs';
const files = ['transcript_full.jsonl', 'transcript.jsonl'];

let foundText = null;
for (const file of files) {
  const filePath = path.join(logDir, file);
  if (!fs.existsSync(filePath)) continue;

  const content = fs.readFileSync(filePath, 'utf8');
  const target = ',ITEMS,UNIT OF MEASURE,UNIT PRICE';
  const idx = content.lastIndexOf(target);
  if (idx !== -1) {
    const raw = content.substring(idx);
    // Find where USER_REQUEST or JSON end
    const end = raw.indexOf('\\n</USER_REQUEST>');
    const chunk = end !== -1 ? raw.substring(0, end) : raw.substring(0, 500000);
    foundText = chunk.replace(/\\n/g, '\n').replace(/\\r/g, '').replace(/\\"/g, '"');
    break;
  }
}

if (foundText) {
  fs.mkdirSync('c:/xampp/htdocs/pharmacy/data', { recursive: true });
  fs.writeFileSync('c:/xampp/htdocs/pharmacy/data/raw_dump.csv', foundText, 'utf8');
  console.log('Saved raw_dump.csv, length:', foundText.length);
} else {
  console.log('Could not find CSV in transcripts');
}

