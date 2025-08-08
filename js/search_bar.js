document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryDropdown = document.getElementById('categoryDropdown');
    const filterToggle = document.getElementById('filterToggle');
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    const selectedCategoriesInput = document.getElementById('selectedCategories');
    const applyBtn = document.getElementById('applyCategories');
    const clearBtn = document.getElementById('clearCategories');
    const selectAllBtn = document.getElementById('selectAllCategories');
    const form = document.getElementById('searchForm');
    const standardBtn = document.getElementById('standardSearchBtn');
    const advancedBtn = document.getElementById('advancedSearchBtn');
    const askBtn = document.getElementById('askBtn');

    let selectedCategories = [];

    // Toggle dropdown visibility
    function toggleDropdown() {
        categoryDropdown.classList.toggle('hidden');
    }

    // Show dropdown when input is focused or filter button is clicked
    searchInput.addEventListener('focus', () => {
        categoryDropdown.classList.remove('hidden');
    });

    filterToggle.addEventListener('click', (e) => {
        e.preventDefault();
        toggleDropdown();
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-input-group')) {
            categoryDropdown.classList.add('hidden');
        }
    });

    // Handle category selection
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCategories);
    });

    function updateSelectedCategories() {
        selectedCategories = Array.from(categoryCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        updateSearchInput();
        selectedCategoriesInput.value = selectedCategories.join(',');
    }

    function updateSearchInput() {
        const currentText = searchInput.value;
        const textWithoutCategories = currentText.replace(/category:\s*"[^"]*"/g, '').trim();
        
        let newValue = textWithoutCategories;
        
        if (selectedCategories.length > 0) {
            const categoryString = selectedCategories.map(cat => `"${cat}"`).join(', ');
            newValue = `category: ${categoryString}${textWithoutCategories ? ' ' + textWithoutCategories : ''}`;
        }
        
        searchInput.value = newValue;
    }

    // Apply categories button
    applyBtn.addEventListener('click', () => {
        updateSelectedCategories();
        categoryDropdown.classList.add('hidden');
        
        // Auto-submit if there are selected categories
        if (selectedCategories.length > 0) {
            performStandardSearch();
        }
    });

    // Clear all categories
    clearBtn.addEventListener('click', () => {
        categoryCheckboxes.forEach(cb => cb.checked = false);
        selectedCategories = [];
        selectedCategoriesInput.value = '';
        
        // Remove category filters from search input
        const currentText = searchInput.value;
        const textWithoutCategories = currentText.replace(/category:\s*"[^"]*"/g, '').trim();
        searchInput.value = textWithoutCategories;
    });

    // Select all categories
    selectAllBtn.addEventListener('click', () => {
        categoryCheckboxes.forEach(cb => cb.checked = true);
        updateSelectedCategories();
    });

    // Search functions
    function performStandardSearch() {
        form.action = "views/book_results.php";
        form.method = "get";
        form.submit();
    }

    function performAdvancedSearch() {
        const value = searchInput.value.trim();
        if (value) {
            const tempForm = document.createElement('form');
            tempForm.method = 'post';
            tempForm.action = 'ask.php';
            
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'question';
            hiddenInput.value = value;
            
            tempForm.appendChild(hiddenInput);
            document.body.appendChild(tempForm);
            tempForm.submit();
        }
    }

    function performAskSearch() {
        const value = searchInput.value.trim();
        if (value) {
            const tempForm = document.createElement('form');
            tempForm.method = 'post';
            tempForm.action = 'ask.php';
            
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'question';
            hiddenInput.value = value;
            
            tempForm.appendChild(hiddenInput);
            document.body.appendChild(tempForm);
            tempForm.submit();
        }
    }

    // Event listeners for buttons
    standardBtn.addEventListener('click', (e) => {
        e.preventDefault();
        performStandardSearch();
    });

    advancedBtn.addEventListener('click', performAdvancedSearch);
    askBtn.addEventListener('click', performAskSearch);

    // Handle Enter key for standard search
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        performStandardSearch();
    });

    // Initialize: Check for existing category selections from URL
    const urlParams = new URLSearchParams(window.location.search);
    const existingCategories = urlParams.get('selected_categories');
    
    if (existingCategories) {
        const categories = existingCategories.split(',');
        categoryCheckboxes.forEach(cb => {
            if (categories.includes(cb.value)) {
                cb.checked = true;
            }
        });
        updateSelectedCategories();
    }
});
