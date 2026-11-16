async function initDashboard() {
  // const closeModalBtns = document.querySelectorAll(".close-modal");

  const selECCutoff = document.getElementById("seleccutoff");
  const selECMonth = document.getElementById("selecmonth");
  const selECYear = document.getElementById("selecyear");

  const selEMMonth = document.getElementById("selemmonth");
  const selEMYear = document.getElementById("selemyear");

  // closeModalBtns.forEach((btn) => {
  //   btn.addEventListener("click", () => {
  //     document.querySelectorAll(".modal").forEach((modal) => {
  //       modal.classList.remove("active");
  //     });
  //   });
  // });

  selECCutoff.addEventListener("change", (e) => {
    getExpensesByCutoff();
  });
  selECMonth.addEventListener("change", (e) => {
    getExpensesByCutoff();
  });
  selECYear.addEventListener("change", (e) => {
    getExpensesByCutoff();
  });

  selEMMonth.addEventListener("change", (e) => {
    getExpensesByMonth();
  });
  selEMYear.addEventListener("change", (e) => {
    getExpensesByMonth();
  });

  getCutoff();
  getExpensesByCutoff();
  getExpensesByMonth();
  getTransactions();
}

async function getCutoff() {
  const selECCutoff = document.getElementById("seleccutoff");
  const selECMonth = document.getElementById("selecmonth");
  const selECYear = document.getElementById("selecyear");

  const selEMMonth = document.getElementById("selemmonth");
  const selEMYear = document.getElementById("selemyear");

  const response = await fetch("controllers/dashboard.php?fn=getCutoff");
  const data = await response.json();

  selECCutoff.innerHTML = data.cutoffs;
  selECMonth.innerHTML = data.months;
  selECYear.innerHTML = data.years;

  selEMMonth.innerHTML = data.months;
  selEMYear.innerHTML = data.years;
}

function getExpensesByCutoff() {
  const expensebycutofftablebody = document
    .getElementById("expensebycutoff-table")
    .querySelector("tbody");
  const expensebycutofftablefoot = document
    .getElementById("expensebycutoff-table")
    .querySelector("tfoot");

  var cutoff = $("#seleccutoff").val();
  var month = $("#selecmonth").val();
  var year = $("#selecyear").val();

  expensebycutofftablebody.innerHTML = "";
  expensebycutofftablefoot.innerHTML = "";

  $.ajax({
    url: "controllers/dashboard.php?fn=getExpensesByCutoff",
    type: "GET",
    dataType: "json",
    data: {
      cutoff: cutoff,
      month: month,
      year: year,
    },
    success: function (data) {
      expensebycutofftablebody.innerHTML = data.tbody;
      expensebycutofftablefoot.innerHTML = data.tfoot;
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      console.error(xhr.responseText);
    },
  });
}

function getExpensesByMonth() {
  const expensebymonthtablebody = document
    .getElementById("expensebymonth-table")
    .querySelector("tbody");
  const expensebymonthtablefoot = document
    .getElementById("expensebymonth-table")
    .querySelector("tfoot");

  var month = $("#selemmonth").val();
  var year = $("#selemyear").val();

  expensebymonthtablebody.innerHTML = "";
  expensebymonthtablefoot.innerHTML = "";

  $.ajax({
    url: "controllers/dashboard.php?fn=getExpensesByMonth",
    type: "GET",
    dataType: "json",
    data: {
      month: month,
      year: year,
    },
    success: function (data) {
      expensebymonthtablebody.innerHTML = data.tbody;
      expensebymonthtablefoot.innerHTML = data.tfoot;
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      console.error(xhr.responseText);
    },
  });
}

function getTransactions() {
  const transactionstablebody = document
    .getElementById("transactions-table")
    .querySelector("tbody");

  transactionstablebody.innerHTML = "";

  $.ajax({
    url: "controllers/dashboard.php?fn=getTransactions",
    type: "GET",
    dataType: "json",
    success: function (data) {
      transactionstablebody.innerHTML = data.tbody;
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      console.error(xhr.responseText);
    },
  });
}
