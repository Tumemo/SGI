const fs = require('node:fs');
const path = require('node:path');
const acorn = require('acorn');
let count = 0;
function walk(directory) {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
        const file = path.join(directory, entry.name);
        if (entry.isDirectory()) walk(file);
        else if (entry.name.endsWith('.js')) {
            try { acorn.parse(fs.readFileSync(file, 'utf8'), { ecmaVersion: 'latest' }); }
            catch (error) { throw new Error(`${file}: ${error.message}`); }
            count++;
        }
    }
}
walk(path.resolve(__dirname, '../resources/js'));
console.log(`${count} arquivos JavaScript válidos.`);
