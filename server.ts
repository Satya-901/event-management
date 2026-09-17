import express from 'express';
import { spawn, ChildProcess } from 'child_process';
import { createProxyMiddleware } from 'http-proxy-middleware';

const app = express();
const PORT = 3000;
const PHP_PORT = 8085;

// Spawn PHP 8+ built-in server using index.php router
let phpProcess: ChildProcess | null = null;
let isShuttingDown = false;

function startPhpServer() {
  if (isShuttingDown) return;

  phpProcess = spawn('php', ['-S', `127.0.0.1:${PHP_PORT}`, 'index.php'], {
    cwd: process.cwd(),
    stdio: ['ignore', 'pipe', 'pipe']
  });

  phpProcess.stdout?.on('data', (data) => {
    process.stdout.write(`[PHP] ${data}`);
  });

  phpProcess.stderr?.on('data', (data) => {
    process.stderr.write(`[PHP] ${data}`);
  });

  phpProcess.on('close', (code) => {
    console.log(`[PHP] Server process exited with code ${code}`);
    if (!isShuttingDown) {
      console.log('[PHP] Respawning PHP process in 500ms...');
      setTimeout(startPhpServer, 500);
    }
  });
}

startPhpServer();

// Health check endpoint for platform probes
app.get('/api/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Proxy all other HTTP and WebSocket requests to the PHP 8 runtime on port 8085
app.use('/', createProxyMiddleware({
  target: `http://127.0.0.1:${PHP_PORT}`,
  changeOrigin: true,
  ws: true,
  on: {
    error: (err, req, res) => {
      console.error('[Proxy Error]:', err.message);
      const expressRes = res as express.Response;
      if (typeof expressRes.status === 'function' && !expressRes.headersSent) {
        expressRes.status(502).send('Gateway Error: Initializing runtime, please refresh momentarily.');
      }
    }
  }
}));

// Graceful process cleanup
const cleanup = () => {
  isShuttingDown = true;
  if (phpProcess) {
    phpProcess.kill('SIGTERM');
    phpProcess = null;
  }
};

process.on('exit', cleanup);
process.on('SIGINT', () => {
  cleanup();
  process.exit(0);
});
process.on('SIGTERM', () => {
  cleanup();
  process.exit(0);
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Server running on http://localhost:${PORT}`);
  console.log(`Utsavam platform operational at http://0.0.0.0:${PORT}`);
});

