document.addEventListener("DOMContentLoaded", async () => {
  await loadHtml();

  const themeToggle = document.getElementById("theme-icon");
  const navItems = document.querySelectorAll(".main-nav li");
  const contentSections = document.querySelectorAll(".content-section");

  await setupEventListeners();

  $("#calendar").evoCalendar({
    theme: "Royal Navy",
  });

  async function loadHtml() {
    const navbar = await fetch("assets/template/navbar.html");
    const navbarhtml = await navbar.text();
    document.querySelector(".app-header").innerHTML = navbarhtml;

    const sidebar = await fetch("assets/template/sidebar.html");
    const sidebarhtml = await sidebar.text();
    document.querySelector(".sidebar").innerHTML = sidebarhtml;

    const dashboard = await fetch("views/dashboard.html");
    const dashboardhtml = await dashboard.text();

    const expenses = await fetch("views/expenses/expenses.html");
    const expenseshtml = await expenses.text();

    document.querySelector(".main-content").innerHTML =
      dashboardhtml + expenseshtml;

    await initDashboard();
    await initExpenses();
  }

  function setupEventListeners() {
    themeToggle.addEventListener("click", toggleTheme);
    navItems.forEach((item) => {
      item.addEventListener("click", () => {
        navItems.forEach((nav) => nav.classList.remove("active"));
        item.classList.add("active");

        const section = item.getAttribute("data-section");
        contentSections.forEach((sec) => sec.classList.remove("active"));
        document.getElementById(section).classList.add("active");

        // Render specific content when section changes
        if (section === "expenses") {
          getExpenses();
        }
      });
    });
  }

  function toggleTheme() {
    const body = document.body;
    if (body.getAttribute("data-theme") === "dark") {
      body.removeAttribute("data-theme");
      themeToggle.classList.remove("fa-sun");
      themeToggle.classList.add("fa-moon");
    } else {
      body.setAttribute("data-theme", "dark");
      themeToggle.classList.remove("fa-moon");
      themeToggle.classList.add("fa-sun");
    }
  }
});
