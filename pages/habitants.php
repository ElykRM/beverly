<?php
include '../db.php';
include '../includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-green-800">Habitants / Residents</h2>
        <a href="../actions/add.php" class="bg-green-700 hover:bg-green-800 text-white font-medium py-2 px-6 rounded-lg shadow transition">
            + New Household
        </a>
    </div>

    <div id="message-container"></div>

    <form id="filter-form" class="bg-white p-6 rounded-xl shadow-md border border-gray-200 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Home Status</label>
                <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="ALL">ALL</option>
                    <option value="Owner">Owner</option>
                    <option value="Renter">Renter</option>
                    <option value="Member">Member</option>
                </select>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Search Name</label>
                <input type="text" name="name" id="name" placeholder="e.g. John or Doe" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
            </div>

            <div class="flex items-end gap-4">
                <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-medium py-2 px-6 rounded-lg transition">
                    Search
                </button>
                <a href="habitants.php" class="text-green-700 hover:text-green-900 underline">Clear Filters</a>
            </div>
        </div>
    </form>
</div>

<div id="table-container" class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
    <!-- Table will be loaded here via AJAX -->
</div>

<script>
// Load table on page load
document.addEventListener('DOMContentLoaded', function() {
    loadTable();
});

// Submit form → load table
document.getElementById('filter-form').addEventListener('submit', function(e) {
    e.preventDefault();
    loadTable();
});

// Instant filter on status change or name input (debounced)
let timeout;
document.getElementById('status').addEventListener('change', debounce(loadTable, 300));
document.getElementById('name').addEventListener('input', debounce(loadTable, 500));

function loadTable() {
    const formData = new FormData(document.getElementById('filter-form'));
    const params = new URLSearchParams(formData).toString();

    fetch(`habitants_table.php?${params}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('table-container').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading table:', error);
        });
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
</script>

<?php include '../includes/footer.php'; ?>