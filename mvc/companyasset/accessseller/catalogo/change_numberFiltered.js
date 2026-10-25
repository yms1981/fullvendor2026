const change_numberFiltered = (number) => {
    console.log("hace el cambio de numberfiltered"+number)
    const total_rows = document.querySelector("[data-total-filtered]")
    total_rows.setAttribute("data-total-filtered",number)
    total_rows.querySelector("span").innerText = number
}
