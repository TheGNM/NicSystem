document.addEventListener("DOMContentLoaded", () => {
  const currentFile = window.location.pathname.split("/").pop();

  const navLinks = document.querySelectorAll(".navbar ul li a");

  navLinks.forEach(link => {
    const linkFile = link.getAttribute("href").split("/").pop();
    
    if (linkFile === currentFile) {
      link.classList.add("active");
    }
  });
});