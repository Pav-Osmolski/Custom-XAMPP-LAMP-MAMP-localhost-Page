function updateClock() {
    const now = new Date();
    const hours = now.getHours().toString().padStart( 2, '0' );
    const minutes = now.getMinutes().toString().padStart( 2, '0' );
    const seconds = now.getSeconds().toString().padStart( 2, '0' );
    document.querySelector( '.clock' ).textContent = `${ hours }:${ minutes }:${ seconds }`;
}

setInterval( updateClock, 1000 );
document.addEventListener( 'DOMContentLoaded', updateClock );

document.addEventListener("DOMContentLoaded", function () {
    const theme = localStorage.getItem("theme");
    if (theme === "light") {
        document.body.classList.add("light-mode");
    }

    document.querySelector(".toggle-theme").addEventListener("click", function () {
        document.body.classList.toggle("light-mode");
        if (document.body.classList.contains("light-mode")) {
            localStorage.setItem("theme", "light");
        } else {
            localStorage.setItem("theme", "dark");
        }
    });
});

function searchProjects() {
    let input = document.querySelector( '.search-bar' ).value.toLowerCase();
    let items = document.querySelectorAll( '.folders li' );

    items.forEach( item => {
        let text = item.textContent.toLowerCase();
        if ( text.includes( input ) ) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    } );
}