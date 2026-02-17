<?php
// docs.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

include 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto; padding-bottom: 5rem;">
    <div style="margin-bottom: 2.5rem;">
        <h1 style="font-size: 2.25rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.025em;">API Documentation</h1>
        <p style="color: var(--text-muted); font-size: 1rem; margin-top: 0.5rem;">Professional developer guide for integrating mobile apps with Calldesk CRM.</p>
    </div>

    <!-- Intro Card -->
    <div class="card" style="margin-bottom: 2.5rem; background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%); border-left: 4px solid var(--primary);">
        <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.75rem;">Base URL</h3>
        <code style="background: white; padding: 0.75rem 1rem; border-radius: 8px; display: block; border: 1px solid var(--border); font-size: 0.9375rem; color: var(--primary); font-weight: 600;">
            http://localhost/calldesk/api/
        </code>
        <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 1rem;">
            <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
            All requests must use <strong>HTTPS</strong> in production. Responses are returned in <strong>JSON</strong> format. Use the <code>Authorization: Bearer &lt;token&gt;</code> header for all requests except login.
        </p>
    </div>

    <!-- Section: Authentication -->
    <div style="margin-bottom: 4rem;">
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
            <span style="background: var(--primary); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">1</span>
            Authentication
        </h2>
        
        <div class="card" style="padding: 1.5rem;">
            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                <span style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 800;">POST</span>
                <code style="font-weight: 700; color: var(--text-main);">/login.php</code>
            </div>
            <p style="font-size: 0.9375rem; color: var(--text-muted); margin-bottom: 1.5rem;">Exchange credentials for an API Bearer Token.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                    <h4 style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem;">Request Body</h4>
                    <pre style="background: #1e293b; color: #e2e8f0; padding: 1.25rem; border-radius: 10px; font-size: 0.8125rem; overflow-x: auto;">{
  "mobile": "9999999999",
  "password": "admin123"
}</pre>
                </div>
                <div>
                    <h4 style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem;">Success Response</h4>
                    <pre style="background: #1e293b; color: #10b981; padding: 1.25rem; border-radius: 10px; font-size: 0.8125rem; overflow-x: auto;">{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "a1b2c3d4e5f6...",
    "user": { "id": 1, "name": "Admin", "role": "admin" }
  }
}</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Tasks -->
    <div style="margin-bottom: 4rem;">
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
            <span style="background: var(--primary); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">2</span>
            Tasks (Follow-ups)
        </h2>
        
        <div class="card" style="padding: 1.5rem;">
            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                <span style="background: #3b82f6; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 800;">GET</span>
                <code style="font-weight: 700; color: var(--text-main);">/tasks.php?filter=today</code>
            </div>
            <p style="font-size: 0.9375rem; color: var(--text-muted); margin-bottom: 1rem;">Fetch tasks assigned to you. Filters: <code>today</code>, <code>upcoming</code>, <code>all</code>.</p>
            
            <div style="background: #f1f5f9; padding: 1.25rem; border-radius: 10px;">
                <h5 style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.75rem;">JSON RESPONSE</h5>
                <pre style="background: transparent; color: var(--text-main); padding: 0; margin: 0; font-size: 0.8125rem; line-height: 1.5;">{
  "success": true,
  "data": {
    "count": 1,
    "tasks": [
      {
        "id": 45,
        "lead_name": "Rajesh Kumar",
        "remark": "Interested, call back today",
        "next_follow_up_date": "2023-11-25"
      }
    ]
  }
}</pre>
            </div>
        </div>
    </div>

    <!-- Section: Interaction -->
    <div style="margin-bottom: 4rem;">
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
            <span style="background: var(--primary); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">3</span>
            Follow-up & Updates
        </h2>
        
        <div class="card" style="padding: 1.5rem;">
            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                <span style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 800;">POST</span>
                <code style="font-weight: 700; color: var(--text-main);">/followups.php</code>
            </div>
            <p style="font-size: 0.9375rem; color: var(--text-muted); margin-bottom: 1.5rem;">Log a new follow-up interaction and optionally update lead status.</p>
            
            <h5 style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.75rem;">PARAMETERS (FORM-DATA)</h5>
            <div class="table-container" style="border: 1px solid var(--border); border-radius: 10px; overflow: hidden; margin-bottom: 1.5rem;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background: #fafafa; border-bottom: 1px solid var(--border);">
                        <th style="padding: 0.75rem 1rem; width: 150px;">Key</th>
                        <th style="padding: 0.75rem 1rem; width: 100px;">Required</th>
                        <th style="padding: 0.75rem 1rem;">Description</th>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.8125rem;">lead_id</td>
                        <td style="padding: 0.75rem 1rem; color: #ef4444; font-size: 0.75rem; font-weight: 700;">YES</td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.8125rem;">ID of the lead</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.8125rem;">remark</td>
                        <td style="padding: 0.75rem 1rem; color: #ef4444; font-size: 0.75rem; font-weight: 700;">YES</td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.8125rem;">Notes about the call</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.8125rem;">status</td>
                        <td style="padding: 0.75rem 1rem; color: var(--text-muted); font-size: 0.75rem;">Optional</td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.8125rem;">New status (Interested, Converted, etc)</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.8125rem;">next_follow_up_date</td>
                        <td style="padding: 0.75rem 1rem; color: var(--text-muted); font-size: 0.75rem;">Optional</td>
                        <td style="padding: 0.75rem 1rem; font-size: 0.8125rem;">YYYY-MM-DD</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Section: Admin Management -->
    <div style="margin-bottom: 4rem;">
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
            <span style="background: var(--primary); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">4</span>
            Admin Management
        </h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="card" style="padding: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem;">
                    <span style="background: #3b82f6; color: white; padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.7rem; font-weight: 800;">GET</span>
                    <code style="font-weight: 700; font-size: 0.875rem;">/executives.php</code>
                </div>
                <p style="font-size: 0.875rem; color: var(--text-muted);">Fetch list of active executives for assignment. <span style="color: #ef4444; font-weight: 700;">(ADMIN ONLY)</span></p>
            </div>
            
            <div class="card" style="padding: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem;">
                    <span style="background: #10b981; color: white; padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.7rem; font-weight: 800;">POST</span>
                    <code style="font-weight: 700; font-size: 0.875rem;">/assign.php</code>
                </div>
                <p style="font-size: 0.875rem; color: var(--text-muted);">Assign <code>lead_id</code> to an executive's <code>assign_to</code> ID. <span style="color: #ef4444; font-weight: 700;">(ADMIN ONLY)</span></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
