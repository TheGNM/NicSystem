async function processSaleAndPrint() {
    if (!confirm('Complete this sale?')) return;

    const form = document.querySelector('form');
    const formData = new FormData(form);

    formData.append('complete_sale', '1');

    try {
        const response = await fetch('process_sale.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            const printFrame = document.getElementById('printFrame');
            printFrame.src = 'receipt.php?invoice=' + result.invoice;

            printFrame.onload = function() {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
                
                alert("Sale completed! Invoice: " + result.invoice);
                
                setTimeout(() => {
                    window.location.href = 'sales.php';
                }, 1000);
            };
        } else {
            alert("Error: " + result.error);
        }
    } catch (error) {
        console.error("Submission failed:", error);
    }
}