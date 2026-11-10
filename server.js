/**
 * Ojasva Events - Node.js Server & Leads Management API
 * Zero external dependencies: uses built-in 'http', 'fs', 'path', 'url'
 * 
 * Usage:
 *   node server.js
 * 
 * Routes:
 *   Website:   http://localhost:3000
 *   Dashboard: http://localhost:3000/dashboard
 *   Login:     http://localhost:3000/login
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');

const PORT = process.env.PORT || 3000;
const LEADS_FILE = path.join(__dirname, 'leads.json');

// Ensure leads.json exists
if (!fs.existsSync(LEADS_FILE)) {
  fs.writeFileSync(LEADS_FILE, JSON.stringify([], null, 2), 'utf8');
}

const MIME_TYPES = {
  '.html': 'text/html; charset=UTF-8',
  '.css': 'text/css; charset=UTF-8',
  '.js': 'application/javascript; charset=UTF-8',
  '.json': 'application/json; charset=UTF-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon',
  '.txt': 'text/plain; charset=UTF-8'
};

function readLeads() {
  try {
    const data = fs.readFileSync(LEADS_FILE, 'utf8');
    return JSON.parse(data || '[]');
  } catch (err) {
    console.error('Error reading leads.json:', err);
    return [];
  }
}

function saveLeads(leads) {
  try {
    fs.writeFileSync(LEADS_FILE, JSON.stringify(leads, null, 2), 'utf8');
    return true;
  } catch (err) {
    console.error('Error writing to leads.json:', err);
    return false;
  }
}

function parseBody(req) {
  return new Promise((resolve, reject) => {
    let body = '';
    req.on('data', chunk => {
      body += chunk.toString();
      if (body.length > 1e6) {
        req.destroy();
        reject(new Error('Payload too large'));
      }
    });
    req.on('end', () => {
      if (!body) return resolve({});
      try {
        resolve(JSON.parse(body));
      } catch (err) {
        reject(err);
      }
    });
  });
}

function sendJson(res, statusCode, data) {
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=UTF-8',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, PATCH, DELETE, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type'
  });
  res.end(JSON.stringify(data));
}

const server = http.createServer(async (req, res) => {
  const parsedUrl = url.parse(req.url, true);
  let pathname = parsedUrl.pathname;
  const method = req.method.toUpperCase();

  // CORS Preflight
  if (method === 'OPTIONS') {
    res.writeHead(204, {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PATCH, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type'
    });
    return res.end();
  }

  // API: Login verification
  if (pathname === '/api/login' && method === 'POST') {
    try {
      const { username, password } = await parseBody(req);
      if (username === 'admin@123' && password === 'admin') {
        return sendJson(res, 200, {
          success: true,
          message: 'Login successful',
          token: 'auth_' + Date.now(),
          user: { username: 'admin@123', role: 'admin' }
        });
      } else {
        return sendJson(res, 401, {
          success: false,
          message: 'Invalid Login ID or Password'
        });
      }
    } catch (err) {
      return sendJson(res, 400, { success: false, message: 'Invalid request data' });
    }
  }

  // API: Get Leads from leads.json
  if (pathname === '/api/leads' && method === 'GET') {
    const leads = readLeads();
    return sendJson(res, 200, leads);
  }

  // API: Save new inquiry into leads.json
  if (pathname === '/api/leads' && method === 'POST') {
    try {
      const body = await parseBody(req);
      const newLead = {
        id: 'lead-' + Date.now(),
        name: body.name || '',
        phone: body.phone || '',
        email: body.email || '',
        event: body.event || 'Other',
        date: body.date || '',
        venue: body.venue || '',
        guests: body.guests || '',
        package: body.package || '',
        budget: body.budget || '',
        details: body.details || '',
        status: 'New',
        submittedAt: new Date().toISOString()
      };

      const leads = readLeads();
      leads.unshift(newLead); // latest on top
      saveLeads(leads);

      return sendJson(res, 201, {
        success: true,
        message: 'Enquiry saved in leads.json',
        lead: newLead,
        totalLeads: leads.length
      });
    } catch (err) {
      return sendJson(res, 400, { success: false, message: 'Failed to parse enquiry' });
    }
  }

  // API: Delete an enquiry
  if (pathname.startsWith('/api/leads/') && method === 'DELETE') {
    const id = pathname.replace('/api/leads/', '');
    let leads = readLeads();
    const originalLength = leads.length;
    leads = leads.filter(l => l.id !== id);
    if (leads.length !== originalLength) {
      saveLeads(leads);
      return sendJson(res, 200, { success: true, message: 'Enquiry deleted', totalLeads: leads.length });
    }
    return sendJson(res, 404, { success: false, message: 'Enquiry not found' });
  }

  // API: Update enquiry status (New / Contacted)
  if (pathname.startsWith('/api/leads/') && method === 'PATCH') {
    const id = pathname.replace('/api/leads/', '');
    try {
      const { status } = await parseBody(req);
      const leads = readLeads();
      const lead = leads.find(l => l.id === id);
      if (lead) {
        lead.status = status || lead.status;
        saveLeads(leads);
        return sendJson(res, 200, { success: true, lead });
      }
      return sendJson(res, 404, { success: false, message: 'Enquiry not found' });
    } catch (err) {
      return sendJson(res, 400, { success: false, message: 'Status update failed' });
    }
  }

  // Clean URL rewrite routing
  if (pathname === '/' || pathname === '') {
    pathname = '/index.html';
  } else if (pathname === '/dashboard') {
    pathname = '/dashboard.html';
  } else if (pathname === '/login') {
    pathname = '/login.html';
  }

  // Static File Serving
  let filePath = path.join(__dirname, pathname);
  const safePath = path.normalize(filePath);
  if (!safePath.startsWith(path.normalize(__dirname))) {
    res.writeHead(403);
    return res.end('Access Denied');
  }

  fs.stat(safePath, (err, stats) => {
    if (err || !stats.isFile()) {
      res.writeHead(404, { 'Content-Type': 'text/html; charset=UTF-8' });
      return res.end('<!doctype html><html><body style="font-family:sans-serif;padding:40px;text-align:center"><h1>404 — Page Not Found</h1><p><a href="/">Return to Ojasva Events Home</a></p></body></html>');
    }

    const ext = path.extname(safePath).toLowerCase();
    const contentType = MIME_TYPES[ext] || 'application/octet-stream';

    res.writeHead(200, { 'Content-Type': contentType });
    fs.createReadStream(safePath).pipe(res);
  });
});

server.listen(PORT, () => {
  console.log(`=======================================================`);
  console.log(`✨ Ojasva Events Server is running!`);
  console.log(`🌐 Website:   http://localhost:${PORT}`);
  console.log(`📊 Dashboard: http://localhost:${PORT}/dashboard`);
  console.log(`🔐 Login:     http://localhost:${PORT}/login`);
  console.log(`   ID:        admin@123`);
  console.log(`   Password:  admin`);
  console.log(`📁 Storage:   ${LEADS_FILE}`);
  console.log(`=======================================================`);
});

