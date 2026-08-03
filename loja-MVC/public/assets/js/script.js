const toggle = document.querySelector(".nav-toggle");
const nav = document.querySelector(".navbar");
toggle.addEventListener("click", function () {
  toggle.classList.toggle("active");
  nav.classList.toggle("active");
});

/* open  cart */
let cartForm = document.querySelector(".carrinho");
document.querySelector("#cart-btn").onclick = () => {
  cartForm.classList.toggle("active");
};

/* close cart */
let closeCart = document.querySelector(".carrinho-close");
closeCart.onclick = () => {
  cartForm.classList.remove("active");
};

/* ---- Popup do vídeo de divulgação ---- */
(function () {
  var modal = document.getElementById("video-modal");
  var video = document.getElementById("video-divulgacao");
  if (!modal || !video) return;

  function abrirVideo() {
    modal.classList.add("active");
    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden"; // trava o scroll de fundo (inclusive no iPhone)
    video.play().catch(function () {
      /* autoplay pode ser bloqueado pelo navegador; usuário usa os controles */
    });
  }

  function fecharVideo() {
    modal.classList.remove("active");
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    video.pause();
    video.currentTime = 0;
  }

  document.querySelectorAll("[data-open-video]").forEach(function (el) {
    el.addEventListener("click", abrirVideo);
  });
  document.querySelectorAll("[data-close-video]").forEach(function (el) {
    el.addEventListener("click", fecharVideo);
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && modal.classList.contains("active")) {
      fecharVideo();
    }
  });
})();





 