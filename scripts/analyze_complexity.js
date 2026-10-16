import fs from 'node:fs';

const content = fs.readFileSync('app.js', 'utf8');

function analyzeFunctions(src) {
  const lines = src.split('\n');
  const results = [];
  const fnDeclRegex = /^(?:\s*)(?:async\s+)?function\s+([a-zA-Z0-9_]+)\s*\(([^)]*)\)/;
  
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const match = line.match(fnDeclRegex);
    if (match) {
      const name = match[1];
      const startLine = i + 1;
      let depth = 0;
      let started = false;
      let complexity = 1;
      let bodyLines = 0;
      
      for (let j = i; j < lines.length; j++) {
        const curLine = lines[j];
        const openBraces = (curLine.match(/\{/g) || []).length;
        const closeBraces = (curLine.match(/\}/g) || []).length;
        
        if (openBraces > 0) started = true;
        depth += openBraces - closeBraces;
        bodyLines++;
        
        const codeOnly = curLine.replace(/\/\/.*$/, '').replace(/\/\*.*?\*\//g, '');
        const ifMatches = (codeOnly.match(/\bif\b/g) || []).length;
        const forMatches = (codeOnly.match(/\bfor\b/g) || []).length;
        const whileMatches = (codeOnly.match(/\bwhile\b/g) || []).length;
        const caseMatches = (codeOnly.match(/\bcase\b/g) || []).length;
        const catchMatches = (codeOnly.match(/\bcatch\b/g) || []).length;
        const andMatches = (codeOnly.match(/&&/g) || []).length;
        const orMatches = (codeOnly.match(/\|\|/g) || []).length;
        const ternaryMatches = (codeOnly.match(/\s\?\s/g) || []).length;
        const nullishMatches = (codeOnly.match(/\?\?/g) || []).length;
        
        complexity += (ifMatches + forMatches + whileMatches + caseMatches + catchMatches + andMatches + orMatches + ternaryMatches + nullishMatches);
        
        if (started && depth <= 0) {
          results.push({
            name,
            startLine,
            endLine: j + 1,
            lineCount: bodyLines,
            complexity
          });
          break;
        }
      }
    }
  }
  return results;
}

const stats = analyzeFunctions(content);
stats.sort((a, b) => b.complexity - a.complexity);

console.log('=== RAPPORT DE COMPLEXITÉ CYCLOMATIQUE (McCabe) ===');
console.log(`Nombre total de fonctions analysées : ${stats.length}`);
const high = stats.filter(f => f.complexity > 15);
const medium = stats.filter(f => f.complexity > 8 && f.complexity <= 15);
const low = stats.filter(f => f.complexity <= 8);

console.log(`• Complexité Élevée (> 15) : ${high.length} fonction(s)`);
console.log(`• Complexité Moyenne (9-15) : ${medium.length} fonction(s)`);
console.log(`• Complexité Faible (1-8)   : ${low.length} fonction(s)\n`);

console.log('Détail des fonctions les plus complexes :');
stats.slice(0, 15).forEach((fn, idx) => {
  const rating = fn.complexity > 15 ? '⚠️ ÉLEVÉE (À refactorer)' : (fn.complexity > 8 ? '⚡ MOYENNE' : '✅ BONNE');
  console.log(`${String(idx + 1).padStart(2, ' ')}. ${fn.name}() - Complexité: ${fn.complexity} (${rating}) | ${fn.lineCount} lignes (L${fn.startLine}-L${fn.endLine})`);
});
