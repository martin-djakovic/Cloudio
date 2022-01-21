let i = 0;

function dropdown() {

    i++;

    if (i > 1) { i = 0; }

    if (i === 1){
        document.querySelector(':root').style.setProperty('--navbar-ht','100%');
    }
    else {
        document.querySelector(':root').style.setProperty('--navbar-ht','70px');
    }
}