/* User Page */
/* Function to handle Profile Dropdown */
function setupProfileDropdown() {
  const profilePic = document.querySelector(".user-img");
  const dropdownMenu = document.getElementById("dropdownMenu");

  function toggleDropdown() {
    dropdownMenu.classList.toggle("active");
  }

  profilePic.addEventListener("click", function (event) {
    event.stopPropagation();
    toggleDropdown();
  });

  document.addEventListener("click", function (event) {
    if (!event.target.closest(".user-img, #dropdownMenu")) {
      dropdownMenu.classList.remove("active");
    }
  });
}

/* Admin Page */
function dropDownAdmin() {
  const hamburger = document.querySelector(".hamburger");
  const dropdownMenuAdmin = document.getElementById("dropdownMenuAdmin");

  function toggleDropdownAdmin() {
    dropdownMenuAdmin.classList.toggle("active");
  }

  hamburger.addEventListener("click", function (event) {
    event.stopPropagation();
    toggleDropdownAdmin();
  });

  // Click anywhere except the dropdown will close it
  document.addEventListener("click", function (event) {
    if (!event.target.closest(".hamburger, #dropdownMenuAdmin")) {
      dropdownMenuAdmin.classList.remove("active");
    }
  });
}


