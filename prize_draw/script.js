document.getElementById('sortForm').addEventListener("submit", function(e){
    e.preventDefault();

    const formData = new FormData(this);

    fetch('sort_names.php',{
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('sortedName').textContent = data;
        const modal = new bootstrap.Modal(document.getElementById('resultModal'));
        modal.show();

        document.getElementById('sort_names').value = '';    
    })
    .catch(error => {
        console.error('Error:', error);
    })
});