import { createReadStream, existsSync, statSync } from 'node:fs';
import { createServer } from 'node:http';
import { extname, join, normalize } from 'node:path';

const root = normalize(process.argv[2] ?? 'build/web');
const port = Number(process.env.PORT ?? 4174);

const contentTypes = {
  '.css': 'text/css; charset=utf-8',
  '.dart': 'application/dart',
  '.html': 'text/html; charset=utf-8',
  '.ico': 'image/x-icon',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.svg': 'image/svg+xml',
  '.wasm': 'application/wasm',
};

createServer((request, response) => {
  const requestPath = decodeURIComponent(request.url?.split('?')[0] ?? '/');
  const candidate = normalize(join(root, requestPath === '/' ? 'index.html' : requestPath));
  const isInsideRoot = candidate.startsWith(root);
  const path = isInsideRoot && existsSync(candidate) && statSync(candidate).isFile()
    ? candidate
    : join(root, 'index.html');

  response.writeHead(200, {
    'Cache-Control': 'no-cache',
    'Content-Type': contentTypes[extname(path)] ?? 'application/octet-stream',
  });
  createReadStream(path).pipe(response);
}).listen(port, '0.0.0.0', () => {
  console.log(`Flutter production preview is available on http://0.0.0.0:${port}`);
});
