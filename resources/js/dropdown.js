document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'product_id[]') {
        updateProductOptions();
    }
});

function updateProductOptions() {
    const allSelects = document.querySelectorAll('select[name="product_id[]"]');
    const selectedValues = Array.from(allSelects)
        .map(select => select.value)
        .filter(value => value !== "");

    allSelects.forEach(select => {
        const options = select.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value === "") return;

            if (selectedValues.includes(option.value) && option.value !== select.value) {
                option.disabled = true;
                option.style.color = '#ccc';
            } else {
                option.disabled = false;
                option.style.color = ''; 
            }
        });
    });
}

window.onload = updateProductOptions;