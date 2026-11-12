const fs = require('fs');
const path = require('path');

const logDir = 'C:/Users/JVGH-PHARMA-LT006/.gemini/antigravity/brain/d2f62483-4fa4-4010-9b0f-6de399df5c55/.system_generated/logs';
const files = ['transcript_full.jsonl', 'transcript.jsonl'];

let found = false;
for (const file of files) {
  const filePath = path.join(logDir, file);
  if (!fs.existsSync(filePath)) continue;

  const content = fs.readFileSync(filePath, 'utf8');
  const targetStr = 'ITEMS,UNIT OF MEASURE,UNIT PRICE';
  const idx = content.lastIndexOf(targetStr);
  if (idx !== -1) {
    // find start of the line
    const lineStart = content.lastIndexOf('\n', idx);
    const start = lineStart !== -1 ? lineStart + 1 : idx;
    
    // We want until the end of the USER_INPUT content field
    // In JSONL, content is inside JSON string or raw text
    // Let's write the chunk out
    const rawChunk = content.substring(start);
    // Find where the JSON field or turn ends
    let end = rawChunk.indexOf('\\n</USER_REQUEST>');
    if (end === -1) end = rawChunk.indexOf('</USER_REQUEST>');
    if (end === -1) end = rawChunk.indexOf('","thinking"');
    if (end === -1) end = rawChunk.indexOf('","tool_calls"');
    if (end === -1) end = rawChunk.length;

    let csvData = rawChunk.substring(0, end);
    // Unescape if it was JSON string
    try {
      csvData = JSON.parse('"' + csvData.replace(/"/g, '\\"') + '"');
    } catch (e) {
      csvData = csvData.replace(/\\n/g, '\n').replace(/\\r/g, '').replace(/\\"/g, '"');
    }

    fs.mkdirSync('c:/xampp/htdocs/pharmacy/data', { recursive: true });
    fs.writeFileSync('c:/xampp/htdocs/pharmacy/data/raw_full_sheet.csv', csvData, 'utf8');
    console.log('Successfully saved raw_full_sheet.csv with', csvData.split('\n').length, 'lines!');
    found = true;
    break;
  }
}

if (!found) {
  console.log('Target string not found in transcripts.');
}

