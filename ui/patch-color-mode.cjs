const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'node_modules/@nuxtjs/color-mode/dist/runtime/nitro-plugin.js');

try {
    let content = fs.readFileSync(filePath, 'utf8');
    if (content.includes('#colmr-mode-options')) {
        console.log('Typo found. Patching...');
        content = content.replace('#colmr-mode-options', '#color-mode-options');
        fs.writeFileSync(filePath, content, 'utf8');
        console.log('Patched successfully.');
    } else {
        console.log('Typo not found or already patched.');
    }
} catch (error) {
    console.error('Error patching file:', error);
    process.exit(1);
}
