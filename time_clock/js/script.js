const hour = document.getElementById('hour');
const minutes = document.getElementById('minutes');
const seconds = document.getElementById('seconds');

const clock = setInterval(function time(){
    let dateToday = new Date();
    let hr  = dateToday.getHours();
    let min = dateToday.getMinutes();
    let s   = dateToday.getSeconds();

    if(hr < 10) hr = '0' + hr;
    if(min < 10) min = '0' + min;
    if(s < 10) s = '0' + s; 

    hour.textContent = hr;
    minutes.textContent = min;
    seconds.textContent = s;
}, 1000); 


let msg = ['Registar Entrada', 'Registrar Pausa', 'Registrar Retorno', 'Registrar Saída'];
let index = 0;
let title = document.querySelector('.msg');
var button = document.querySelector('.button');

button.addEventListener('click', function(){
    button.setAttribute('data-action', msg[index].toLowerCase().replace(/ /g, '_'));
    window.location.href = `register.php?action=${encodeURIComponent(msg[index])}`;
    if(index + 1 == msg.length){
        index = 0;
    }else{
        index = index+1;
    }
    title.textContent = msg[index];

    
})
