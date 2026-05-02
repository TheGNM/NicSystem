let sortDirection = true;

function sortTable() {
    const table = document.querySelector("table tbody");
    const rows = Array.from(table.querySelectorAll("tr"));
    const icon = document.getElementById("sort-icon");

    rows.sort((a, b) => {
        const idA = parseInt(a.cells[0].innerText);
        const idB = parseInt(b.cells[0].innerText);

        return sortDirection ? idA - idB : idB - idA;
    });

    rows.forEach(row => table.appendChild(row));

    sortDirection = !sortDirection;
    icon.innerText = sortDirection ? "▲" : "▼";
}