// Send login status from PHP to JavaScript
const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
const username = <?php echo json_encode($username); ?>;

if (isLoggedIn) {
    document.querySelector('.nav-login-btn').style.display = 'none';
    document.querySelector('.nav-signup-btn').style.display = 'none';
    const userElement = document.querySelector('.nav-user');
    userElement.style.display = 'inline-block';
    userElement.querySelector('span').textContent = username;

    // Add event listener for dropdown
    userElement.addEventListener('click', function(event) {
        const dropdownContent = this.querySelector('.dropdown-content');
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
        event.stopPropagation(); // Prevent the document click event from firing immediately
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdownContent = document.querySelector('.nav-user .dropdown-content');
        if (!userElement.contains(event.target)) {
            dropdownContent.style.display = 'none';
        }
    });
} else {
    // Handle "Take a test" link click for non-logged-in users
    document.getElementById('take-test-link').addEventListener('click', function(event) {
        event.preventDefault();
        const modal = document.getElementById('loginModal');
        modal.style.display = 'block';
    });

    // Get the modal
    const modal = document.getElementById('loginModal');

    // Get the <span> element that closes the modal
    const span = document.getElementsByClassName('close')[0];

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = 'none';
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}
