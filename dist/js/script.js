// const notificationBar = document.getElementById('notification-bar');
// const notificationText = document.getElementById('notification-text');
// const showNotificationButton = document.getElementById('show-notification');

// showNotificationButton.addEventListener('click', () => {
//     const circle = document.createElement('div');
//     circle.classList.add('circle');
//     notificationBar.appendChild(circle);

//     setTimeout(() => {
//         circle.remove();
//         notificationBar.classList.add('show');
//         notificationText.classList.add('show');
//         notificationText.textContent = 'This is a notification!';
//     }, 500);

//     setTimeout(() => {
//         notificationBar.classList.remove('show');
//         notificationText.classList.remove('show');
//         notificationBar.style.width = '0px'; // add this line
//     }, 3000);
// });

// const notificationBar = document.querySelector('.notification-bar');
// const notificationText = document.querySelector('.notification-text');

// notificationBar.addEventListener('click', () => {
//     notificationBar.classList.toggle('show');
//     notificationText.classList.toggle('show');
// });



const wrapper = document.querySelector(".wrapper");
const carousel = document.querySelector(".carousel");
const firstCardWidth = carousel.querySelector(".card").offsetWidth;
const arrowBtns = document.querySelectorAll(".wrapper i");
const carouselChildrens = [...carousel.children];

let isDragging = false, isAutoPlay = true, startX, startScrollLeft, timeoutId;

// Get the number of cards that can fit in the carousel at once
let cardPerView = Math.round(carousel.offsetWidth / firstCardWidth);

// Insert copies of the last few cards to beginning of carousel for infinite scrolling
carouselChildrens.slice(-cardPerView).reverse().forEach(card => {
    carousel.insertAdjacentHTML("afterbegin", card.outerHTML);
});

// Insert copies of the first few cards to end of carousel for infinite scrolling
carouselChildrens.slice(0, cardPerView).forEach(card => {
    carousel.insertAdjacentHTML("beforeend", card.outerHTML);
});

// Scroll the carousel at appropriate postition to hide first few duplicate cards on Firefox
carousel.classList.add("no-transition");
carousel.scrollLeft = carousel.offsetWidth;
carousel.classList.remove("no-transition");

// Add event listeners for the arrow buttons to scroll the carousel left and right
arrowBtns.forEach(btn => {
    btn.addEventListener("click", () => {
        carousel.scrollLeft += btn.id == "left" ? -firstCardWidth : firstCardWidth;
    });
});

const dragStart = (e) => {
    isDragging = true;
    carousel.classList.add("dragging");
    // Records the initial cursor and scroll position of the carousel
    startX = e.pageX;
    startScrollLeft = carousel.scrollLeft;
}

const dragging = (e) => {
    if(!isDragging) return; // if isDragging is false return from here
    // Updates the scroll position of the carousel based on the cursor movement
    carousel.scrollLeft = startScrollLeft - (e.pageX - startX);
}

const dragStop = () => {
    isDragging = false;
    carousel.classList.remove("dragging");
}

const infiniteScroll = () => {
    // If the carousel is at the beginning, scroll to the end
    if(carousel.scrollLeft === 0) {
        carousel.classList.add("no-transition");
        carousel.scrollLeft = carousel.scrollWidth - (2 * carousel.offsetWidth);
        carousel.classList.remove("no-transition");
    }
    // If the carousel is at the end, scroll to the beginning
    else if(Math.ceil(carousel.scrollLeft) === carousel.scrollWidth - carousel.offsetWidth) {
        carousel.classList.add("no-transition");
        carousel.scrollLeft = carousel.offsetWidth;
        carousel.classList.remove("no-transition");
    }

}


carousel.addEventListener("mousedown", dragStart);
carousel.addEventListener("mousemove", dragging);
document.addEventListener("mouseup", dragStop);
carousel.addEventListener("scroll", infiniteScroll);
wrapper.addEventListener("mouseenter", () => clearTimeout(timeoutId));









const formOpenBtn = document.querySelector("#form-open"),
  home = document.querySelector(".home"),
  formContainer = document.querySelector(".form_container"),
  formCloseBtn = document.querySelector(".form_close"),
  signupBtn = document.querySelector("#signup"),
  loginBtn = document.querySelector("#login"),
  pwShowHide = document.querySelectorAll(".pw_hide");

formOpenBtn.addEventListener("click", () => home.classList.add("show"));
formCloseBtn.addEventListener("click", () => home.classList.remove("show"));

pwShowHide.forEach((icon) => {
  icon.addEventListener("click", () => {
    let getPwInput = icon.parentElement.querySelector("input");
    if (getPwInput.type === "password") {
      getPwInput.type = "text";
      icon.classList.replace("uil-eye-slash", "uil-eye");
    } else {
      getPwInput.type = "password";
      icon.classList.replace("uil-eye", "uil-eye-slash");
    }
  });
});

signupBtn.addEventListener("click", (e) => {
  e.preventDefault();
  formContainer.classList.add("active");
});
loginBtn.addEventListener("click", (e) => {
  e.preventDefault();
  formContainer.classList.remove("active");
});




const createPw = document.querySelector("#create_pw"),
 confirmPw = document.querySelector("#confirm_pw"),
 pwShow = document.querySelector(".show"),
 alertIcon = document.querySelector(".error"),
 alertText= document.querySelector(".text"),
 submitBtn = document.querySelector("#button");

 pwShow.addEventListener("click", ()=>{
   if((createPw.type === "password") && (confirmPw.type === "password")){
     createPw.type = "text";
     confirmPw.type = "text";
     pwShow.classList.replace("fa-eye-slash","fa-eye");
   }else {
     createPw.type = "password";
     confirmPw.type = "password";
     pwShow.classList.replace("fa-eye","fa-eye-slash");
   }
 });

 createPw.addEventListener("input", ()=>{
   let val = createPw.value.trim()
   if(val.length >= 8){
     confirmPw.removeAttribute("disabled");
     submitBtn.removeAttribute("disabled");
     submitBtn.classList.add("active");
   }else {
     confirmPw.setAttribute("disabled", true);
     submitBtn.setAttribute("disabled", true);
     submitBtn.classList.remove("active");
     confirmPw.value = "";
     alertText.style.color = "#a6a6a6";
     alertText.innerText = "Enter at least 8 characters";
     alertIcon.style.display = "none";
   }
 });

submitBtn.addEventListener("click", ()=>{
 if(createPw.value === confirmPw.value){
   alertText.innerText = "Password matched";
   alertIcon.style.display = "none";
   alertText.style.color = "#4070F4";
 }else {
   alertText.innerText = "Password didn't matched";
   alertIcon.style.display = "block";
   alertText.style.color = "#D93025";
 }
});
