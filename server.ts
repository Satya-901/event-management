import express from 'express';
import { spawn, execSync, ChildProcess } from 'child_process';
import { createProxyMiddleware } from 'http-proxy-middleware';

const app = express();
const PORT = 3000;
const PHP_PORT = 8085;

// Spawn PHP 8+ built-in server using index.php router
let phpProcess: ChildProcess | null = null;
let isShuttingDown = false;

function ensurePhpInstalled(): boolean {
  try {
    execSync('php -v', { stdio: 'ignore' });
    return true;
  } catch {
    console.log('[Setup] PHP runtime not found. Auto-installing PHP CLI and modules...');
    try {
      execSync('DEBIAN_FRONTEND=noninteractive apt-get update -qq && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" php-cli php-sqlite3 php-curl php-mbstring php-xml', { stdio: 'inherit' });
      return true;
    } catch (installErr) {
      console.error('[Setup] Failed to auto-install PHP:', installErr);
      return false;
    }
  }
}

function startPhpServer() {
  if (isShuttingDown) return;

  if (!ensurePhpInstalled()) {
    console.error('[PHP] PHP is not available. Retrying in 5000ms...');
    setTimeout(startPhpServer, 5000);
    return;
  }

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

  phpProcess.on('error', (err) => {
    console.error('[PHP] Process error:', err);
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
  xfwd: true,
  on: {
    proxyRes: (proxyRes) => {
      const cookies = proxyRes.headers['set-cookie'];
      if (cookies) {
        const list = Array.isArray(cookies) ? cookies : [cookies];
        proxyRes.headers['set-cookie'] = list.map((c) => {
          let str = c;
          if (!/samesite=/i.test(str)) {
            str += '; SameSite=None';
          } else {
            str = str.replace(/samesite=[^;]+/i, 'SameSite=None');
          }
          if (!/;\s*secure/i.test(str)) {
            str += '; Secure';
          }
          if (!/;\s*partitioned/i.test(str)) {
            str += '; Partitioned';
          }
          if (!/path=/i.test(str)) {
            str += '; Path=/';
          }
          return str;
        });
      }
    },
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

