document.addEventListener('DOMContentLoaded', function() {
    // Search toggle interactions
    const toggleSearch = document.getElementById('toggle-search');
    const closeSearch = document.getElementById('close-search');
    const searchBar = document.getElementById('header-search-bar');
    const searchInput = document.getElementById('search-input');

    if (toggleSearch && closeSearch && searchBar) {
        toggleSearch.addEventListener('click', function(e) {
            e.preventDefault();
            if (searchBar.style.display === 'none' || searchBar.style.display === '') {
                searchBar.style.display = 'block';
                if (searchInput) {
                    searchInput.focus();
                }
            } else {
                searchBar.style.display = 'none';
            }
        });

        closeSearch.addEventListener('click', function(e) {
            e.preventDefault();
            searchBar.style.display = 'none';
        });
        
        // Close search on Esc key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchBar.style.display = 'none';
            }
        });
    }
});
