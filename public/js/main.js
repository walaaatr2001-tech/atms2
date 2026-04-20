// AT-AMS Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize Tom Select for dropdowns
    if (typeof TomSelect !== 'undefined') {
        document.querySelectorAll('select[data-select]').forEach(function(el) {
            new TomSelect(el, {
                create: false,
                placeholder: el.dataset.placeholder || 'Sélectionner...'
            });
        });
    }
    
    // City dropdown handler for wilaya selection
    const wilayaSelect = document.getElementById('wilaya_id');
    const citySelect = document.getElementById('city_id');
    
    if (wilayaSelect && citySelect) {
        wilayaSelect.addEventListener('change', function() {
            const wilayaId = this.value;
            
            // Reset city dropdown
            citySelect.innerHTML = '<option value="">Sélectionner une ville</option>';
            citySelect.disabled = !wilayaId;
            
            if (wilayaId) {
                fetchCities(wilayaId);
            }
        });
    }
    
    // File upload preview
    const fileInput = document.getElementById('file_input');
    const filePreview = document.getElementById('file_preview');
    
    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const size = (file.size / 1024 / 1024).toFixed(2);
                filePreview.innerHTML = `
                    <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                        <span class="text-2xl">📎</span>
                        <div>
                            <p class="font-medium text-gray-800">${file.name}</p>
                            <p class="text-sm text-gray-500">${size} MB</p>
                        </div>
                    </div>
                `;
            }
        });
    }
    
    // Delete confirmation
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément?')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Fetch cities from database via AJAX
function fetchCities(wilayaId) {
    fetch('../../controllers/get_cities.php?wilaya_id=' + wilayaId)
        .then(response => response.json())
        .then(data => {
            const citySelect = document.getElementById('city_id');
            if (data.success) {
                data.cities.forEach(function(city) {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name_fr;
                    citySelect.appendChild(option);
                });
                citySelect.disabled = false;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Update document status
function updateStatus(docId, status) {
    if (confirm('Êtes-vous sûr de vouloir ' + (status === 'validated' ? 'valider' : 'rejeter') + ' ce document?')) {
        window.location.href = '../../controllers/update_status.php?id=' + docId + '&status=' + status;
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}