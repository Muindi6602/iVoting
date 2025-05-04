    // Load recent activity
    function loadRecentActivity() {
        $.ajax({
            url: '../api/activity.php',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#recentActivity').html(response.html);
                }
            }
        });
    }
    
    loadRecentActivity();
    
    // Form validation
    (function() {
        'use strict';
        
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
});