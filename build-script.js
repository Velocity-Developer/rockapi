const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

// Create dist directory if it doesn't exist, or clean it if it does
const distDir = path.join(__dirname, 'dist');
if (fs.existsSync(distDir)) {
    // Clean dist directory
    const files = fs.readdirSync(distDir);
    for (const file of files) {
        const filePath = path.join(distDir, file);
        if (fs.lstatSync(filePath).isDirectory()) {
            fs.rmSync(filePath, { recursive: true, force: true });
        } else {
            fs.unlinkSync(filePath);
        }
    }
    console.log('Dist directory cleaned.');
} else {
    fs.mkdirSync(distDir, { recursive: true });
    console.log('Dist directory created.');
}

// Daftar file dan folder yang dikecualikan
const excludePatterns = [
  'node_modules',
  '.git',
  '.github',
  'storage/',
  'bootstrap/cache/*',
  '.env',
  '.env.example',
  '.gitignore',
  '.gitattributes',
  '.editorconfig',
  'phpunit.xml',
  'tests',
  'README.md',
  'package.json',
  'package-lock.json',
  'build-script.js',
  'composer.lock',
  'yarn.lock',
  '.DS_Store',
  'Thumbs.db',
  '*.log',
  'build-*.zip',
  'build.zip',
  '.vscode',
  'BUILD.md',
  'vendor/',
  'public/storage/'
];

// Fungsi untuk mengecek apakah file/folder harus dikecualikan
function shouldExclude(filePath) {
  const relativePath = path.relative(__dirname, filePath);
  
  return excludePatterns.some(pattern => {
    if (pattern.includes('*')) {
      // Handle wildcard patterns
      const regex = new RegExp(pattern.replace(/\*/g, '.*'));
      return regex.test(relativePath);
    } else {
      // Handle exact matches and directory matches
      return relativePath === pattern || 
             relativePath.startsWith(pattern + path.sep) ||
             relativePath.includes(path.sep + pattern + path.sep) ||
             relativePath.endsWith(path.sep + pattern);
    }
  });
}

// Fungsi utama untuk membuat zip
async function createBuildZip() {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
  const outputPath = path.join(distDir, `build.zip`);
  
  console.log('🚀 Memulai proses build Laravel untuk production...');
  console.log(`📦 Output: ${outputPath}`);
  
  const output = fs.createWriteStream(outputPath);
  const archive = archiver('zip', {
    zlib: { level: 9 } // Kompresi maksimal
  });
  
  return new Promise((resolve, reject) => {
    output.on('close', () => {
      const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
      console.log(`✅ Build selesai! Size: ${sizeInMB} MB`);
      console.log(`📁 File tersimpan: ${outputPath}`);
      console.log(`📍 Location: dist/${path.basename(outputPath)}`);
      resolve();
    });
    
    archive.on('error', (err) => {
      console.error('❌ Error saat membuat zip:', err);
      reject(err);
    });
    
    archive.pipe(output);
    
    // Tambahkan semua file kecuali yang dikecualikan
    function addDirectory(dirPath, zipPath = '') {
      const items = fs.readdirSync(dirPath);
      
      items.forEach(item => {
        const fullPath = path.join(dirPath, item);
        const zipItemPath = zipPath ? path.join(zipPath, item) : item;
        
        if (shouldExclude(fullPath)) {
          console.log(`⏭️  Mengabaikan: ${zipItemPath}`);
          return;
        }
        
        let stat;
        try {
          stat = fs.lstatSync(fullPath);
        } catch (err) {
          console.log(`⚠️  Tidak dapat mengakses: ${zipItemPath} (${err.code})`);
          return;
        }
        
        // Skip symbolic links
        if (stat.isSymbolicLink()) {
          console.log(`🔗 Mengabaikan symbolic link: ${zipItemPath}`);
          return;
        }
        
        if (stat.isDirectory()) {
          addDirectory(fullPath, zipItemPath);
        } else {
          console.log(`📄 Menambahkan: ${zipItemPath}`);
          archive.file(fullPath, { name: zipItemPath });
        }
      });
    }
    
    // Mulai dari root directory
    addDirectory(__dirname);
    
    // Finalisasi archive
    archive.finalize();
  });
}

// Jalankan build
createBuildZip().catch(console.error);