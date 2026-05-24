<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegance Salon | Service Inventory Control</title>
    <style>
        :root { --gold: #c5a059; --dark: #1b1b1b; --gray: #f4f4f4; --border: #e0e0e0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 25px; background: #faf9f6; color: #333; }
        .wrapper { max-width: 1100px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        
        .header-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid var(--gold); padding-bottom: 15px; }
        .btn { padding: 10px 20px; font-weight: bold; border-radius: 4px; border: none; cursor: pointer; text-transform: uppercase; font-size: 0.85rem; }
        .btn-add { background: var(--dark); color: white; }
        .btn-add:hover { background: var(--gold); }
        .btn-edit { background: #e0f2fe; color: #0369a1; margin-right: 5px; }
        .btn-delete { background: #fce8e6; color: #c5221f; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #fcfbfa; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; }
        
        /* Modal Window Styling */
        .modal { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:none; justify-content:center; align-items:center; z-index: 100; }
        .modal-content { background: #fff; padding: 30px; border-radius: 6px; width: 90%; max-width: 500px; position: relative; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.85rem; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; }
        textarea { height: 80px; resize: vertical; }
        
        .notify { padding: 12px; border-radius: 4px; margin-bottom: 20px; display: none; font-weight: bold; }
        .notify.success { background: #e6f4ea; color: #137333; border: 1px solid #137333; }
        .notify.error { background: #fce8e6; color: #c5221f; border: 1px solid #c5221f; }
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 1.5rem; cursor: pointer; color: #888; }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="header-action">
        <h2>Service Management Engine</h2>
        <button class="btn btn-add" onclick="showFormModal(false)">+ Add New Service</button>
    </div>

    <div id="alertTray" class="notify"></div>

    <table id="servicesTable">
        <thead>
            <tr>
                <th>Category</th>
                <th>Service Title</th>
                <th>Description Preview</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <tr><td colspan="4" style="text-align:center; color: var(--gold);">Loading registry configuration matrices...</td></tr>
        </tbody>
    </table>
</div>

<div class="modal" id="formModal">
    <div class="modal-content">
        <span class="close-btn" onclick="hideFormModal()">&times;</span>
        <h3 id="modalTitle">Add Service Entry</h3>
        <form id="crudForm">
            <input type="hidden" id="serviceId">
            <div class="form-group">
                <label>Operational Category</label>
                <select id="category">
                    <option value="Hair">Hair Sculpting</option>
                    <option value="Skin">Skin Treatments</option>
                    <option value="Nails">Nail Artistry</option>
                </select>
            </div>
            <div class="form-group">
                <label>Service Title</label>
                <input type="text" id="title" required placeholder="e.g. Therapeutic Spa Pedicure">
            </div>
            <div class="form-group">
                <label>Detailed Service Description</label>
                <textarea id="description" required placeholder="Outline procedure parameters explicitly..."></textarea>
            </div>
            <div class="form-group">
                <label>Display Asset Image URL</label>
                <input type="text" id="image_url" required placeholder="https://domain.com/assets/img.jpg">
            </div>
            <button type="submit" class="btn btn-add" style="width:100%; padding:12px;" id="submitBtn">Save Record</button>
        </form>
    </div>
</div>

<script>
    const endpoint = 'crud_api.php';
    let editMode = false;

    async function fetchAllServices() {
        try {
            const res = await fetch(`${endpoint}?nocache=${new Date().getTime()}`);
            const data = await res.json();
            const tbody = document.getElementById('tableBody');
            
            if(data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#777;">No dynamic rows found. Click add to register a system service.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.map(s => `
                <tr>
                    <td><strong>${escapeHtml(s.category)}</strong></td>
                    <td>${escapeHtml(s.title)}</td>
                    <td style="color:#666; font-size:0.9rem;">${escapeHtml(s.description.substring(0, 75))}...</td>
                    <td>
                        <button class="btn btn-edit" onclick="initiateEdit(${s.id})">Edit</button>
                        <button class="btn btn-delete" onclick="triggerDelete(${s.id})">Delete</button>
                    </td>
                </tr>
            `).join('');
        } catch (err) {
            showAlert("Failed to evaluate dataset from core API node.", 'error');
        }
    }

    function showFormModal(isEdit = false) {
        editMode = isEdit;
        document.getElementById('modalTitle').innerText = isEdit ? "Modify Existing Service Entry" : "Register New Service Entry";
        document.getElementById('formModal').style.display = 'flex';
    }

    function hideFormModal() {
        document.getElementById('formModal').style.display = 'none';
        document.getElementById('crudForm').reset();
        document.getElementById('serviceId').value = '';
    }

    async function initiateEdit(id) {
        try {
            const res = await fetch(`${endpoint}?id=${id}`);
            const service = await res.json();
            
            document.getElementById('serviceId').value = service.id;
            document.getElementById('title').value = service.title;
            document.getElementById('description').value = service.description;
            document.getElementById('image_url').value = service.image_url;
            document.getElementById('category').value = service.category;
            
            showFormModal(true);
        } catch (err) {
            showAlert("Unable to track transaction index parameters.", 'error');
        }
    }

    document.getElementById('crudForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            id: document.getElementById('serviceId').value,
            title: document.getElementById('title').value,
            description: document.getElementById('description').value,
            image_url: document.getElementById('image_url').value,
            category: document.getElementById('category').value
        };

        const config = {
            method: editMode ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        };

        try {
            const res = await fetch(endpoint, config);
            const statusMessage = await res.json();
            showAlert(statusMessage.message, statusMessage.status === 'success' ? 'success' : 'error');
            hideFormModal();
            fetchAllServices();
        } catch (err) {
            showAlert("Transaction processing failure.", 'error');
        }
    });

    async function triggerDelete(id) {
        if (!confirm("Are you entirely sure you want to delete this service record? This action cannot be undone.")) return;

        try {
            const res = await fetch(endpoint, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const statusMessage = await res.json();
            showAlert(statusMessage.message, 'success');
            fetchAllServices();
        } catch (err) {
            showAlert("Record removal execution failed.", 'error');
        }
    }

    function showAlert(msg, type) {
        const tray = document.getElementById('alertTray');
        tray.className = `notify ${type}`;
        tray.innerText = msg;
        tray.style.display = 'block';
        setTimeout(() => { tray.style.display = 'none'; }, 5000);
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', fetchAllServices);
</script>
</body>
</html>
