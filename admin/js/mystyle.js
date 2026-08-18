

 // --- Sidebar Dropdown Functionality ---
    document.addEventListener('DOMContentLoaded', () => {
        const dropdownButtons = document.querySelectorAll('.sidebar-link[data-dropdown]');

        dropdownButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default link behavior

                const dropdownId = this.getAttribute('data-dropdown');
                const submenu = document.getElementById(dropdownId);
                const chevronIcon = this.querySelector('.bx-chevron-right');

                if (submenu) {
                    // Toggle the 'open' class for CSS transition (max-height)
                    submenu.classList.toggle('open');
                    
                    // Rotate the chevron icon
                    if (chevronIcon) {
                        chevronIcon.classList.toggle('rotate-90');
                    }
                }
            });
        });
    });
	
	
	
	
	