async function initExpenses() {
  const closeModalBtns = document.querySelectorAll(".close-modal");
  const addExpensesBtn = document.getElementById("add-expenses");

  const selPayInterval = document.getElementById("selpayinterval");

  const addExpensesModal = document.getElementById("add-expenses-modal");

  const expensesDetailModal = document.getElementById("expenses-detail-modal");

  const formExpenses = document.getElementById("add-expenses-form");

  closeModalBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll(".modal").forEach((modal) => {
        modal.classList.remove("active");
      });
    });
  });

  document.addEventListener("click", (e) => {
    const paidBtn = e.target.closest(".paid-button");
    if (!paidBtn) return;
    const data = paidBtn.dataset.id;
    paidExpense(data);
  });

  selPayInterval.addEventListener("change", (e) => {
    const divStartDate = document.getElementById("divstartdate");
    const divEndDate = document.getElementById("divenddate");
    const divCustomDay = document.getElementById("divcustomday");
    const txtCustomDay = document.getElementById("txtcustomday");
    const value = e.target.value;

    if (value === "Once") {
      divStartDate.style.display = "block";
      divEndDate.style.display = "none";
      divCustomDay.style.display = "none";
      txtCustomDay.placeholder = "";
    } else if (value === "Daily") {
      divStartDate.style.display = "block";
      divEndDate.style.display = "block";
      divCustomDay.style.display = "none";
      txtCustomDay.placeholder = "";
    } else if (value === "Weekly") {
      divStartDate.style.display = "block";
      divEndDate.style.display = "block";
      divCustomDay.style.display = "block";
      txtCustomDay.placeholder = "Enter week day";
    } else if (value === "Bimonthly") {
      divStartDate.style.display = "block";
      divEndDate.style.display = "block";
      divCustomDay.style.display = "block";
      txtCustomDay.placeholder = "Enter two day (comma seperated)";
    } else if (value === "Monthly") {
      divStartDate.style.display = "block";
      divEndDate.style.display = "block";
      divCustomDay.style.display = "block";
      txtCustomDay.placeholder = "Enter Date";
    } else if (value === "Custom") {
      divStartDate.style.display = "block";
      divEndDate.style.display = "block";
      divCustomDay.style.display = "block";
      txtCustomDay.placeholder = "comma seperated";
    }
  });

  addExpensesBtn.addEventListener("click", () => {
    addExpensesModal.classList.add("active");
  });

  formExpenses.addEventListener("submit", (e) => {
    e.preventDefault();

    let haveError = false;
    const rawForm = new FormData(formExpenses);
    const formData = Object.fromEntries(rawForm);

    if (
      formData["selcategory"] == "" ||
      formData["txtexpensename"] == "" ||
      formData["selpayinterval"] == "" ||
      formData["txtamount"] == ""
    )
      haveError = true;

    if (formData["selpayinterval"] == "Once" && formData["txtstartdate"] == "")
      haveError = true;
    if (
      formData["selpayinterval"] == "Daily" &&
      (formData["txtstartdate"] == "" || formData["txtenddate"] == "")
    )
      haveError = true;
    if (
      ["Weekly", "Bimonthly", "Monthly", "Custom"].includes(
        formData["selpayinterval"],
      ) &&
      (formData["txtstartdate"] == "" ||
        formData["txtenddate"] == "" ||
        formData["txtcustomday"] == "")
    )
      haveError = true;

    if (haveError) {
      Swal.fire({
        text: "Please provide values for all fields marked with an asterisk (*).",
        icon: "error",
      });
    } else if (
      !Number.isFinite(parseFloat(formData["txtamount"])) ||
      formData["txtamount"] <= 0
    ) {
      Swal.fire({
        text: "Amount must be a number greater than zero.",
        icon: "error",
      });
    } else {
      saveExpenses(formData);
    }
  });

  $("#calendar").on("selectEvent", function (event, activeEvent) {
    getExpensesDetail(activeEvent.id);
    expensesDetailModal.classList.add("active");
  });

  await getCategory();
  await getPayInterval();
}

async function getCategory() {
  const selCategory = document.getElementById("selcategory");

  const response = await fetch("controllers/expenses.php?fn=getCategory");
  const data = await response.json();

  selCategory.innerHTML = data.response;
}

async function getPayInterval() {
  const selPayInterval = document.getElementById("selpayinterval");

  const response = await fetch("controllers/expenses.php?fn=getInterval");
  const data = await response.json();

  selPayInterval.innerHTML = data.response;
}

async function getExpenses() {
  resetCalendar();
  const response = await fetch("controllers/expenses.php?fn=getExpenses");
  const data = await response.json();

  $("#calendar").evoCalendar("addCalendarEvent", data.response);
}

function getExpensesDetail(expensecode) {
  $.ajax({
    url: "controllers/expenses.php?fn=getExpense",
    type: "GET",
    dataType: "json",
    data: {
      expensecode: expensecode,
    },
    success: function (data) {
      $("#dtl-status").html(data.response.status);
      $("#detail-timeline").html(data.response.timeline);
      $("#dtl-category").html(data.response.detail.CATEGORY);
      $("#dtl-expensename").html(data.response.detail.EXPENSENAME);
      $("#dtl-expensecode").html(`(${data.response.detail.EXPENSECODE})`);
      $("#dtl-totalpaid").html(data.response.detail.TOTALPAID);
      $("#dtl-totalamount").html(data.response.detail.TOTALAMOUNT);
      $("#dtl-balance").html(data.response.detail.BALANCE);
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      console.error(xhr.responseText);
    },
  });
}

function saveExpenses(formdata) {
  $.ajax({
    url: "controllers/expenses.php?fn=saveExpenses",
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify(formdata),
    dataType: "json",
    success: function (data) {
      if (data.status != 200) {
        Swal.fire({
          title: data.error,
          icon: "error",
        });
        return;
      }

      Swal.fire({
        title: "Expense saved successfully!",
        icon: "success",
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.reload();
        }
      });
    },
    error: function (xhr, status, error) {
      let message = error;

      try {
        const response = JSON.parse(xhr.responseText);
        message = response.error || error;
      } catch (e) {
        message = xhr.responseText || error;
      }

      Swal.fire({
        title: message,
        icon: "error",
      });
    },
  });
}

function paidExpense(data) {
  const [expensecode, term, paiddate] = data.split("|");
  $.ajax({
    url: "controllers/expenses.php?fn=paidExpense",
    type: "POST",
    data: {
      expensecode: expensecode,
      term: term,
      paiddate: paiddate,
    },
    dataType: "json",
    success: function (data) {
      if (data.status != 200) {
        Swal.fire({
          title: data.error,
          icon: "error",
        });
        return;
      }

      Swal.fire({
        title: "Expense saved successfully!",
        icon: "success",
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.reload();
        }
      });
    },
    error: function (xhr, status, error) {
      let message = error;

      try {
        const response = JSON.parse(xhr.responseText);
        message = response.error || error;
      } catch (e) {
        message = xhr.responseText || error;
      }

      Swal.fire({
        title: message,
        icon: "error",
      });
    },
  });
}

function resetCalendar() {
  $("#calendar").evoCalendar("destroy");
  $("#calendar").evoCalendar({
    theme: "Royal Navy",
  });
}
