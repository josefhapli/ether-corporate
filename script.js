const toggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#navigation');
function closeMenu() {
  toggle.setAttribute('aria-expanded', 'false');
  navigation.classList.remove('is-open');
}
toggle.addEventListener('click', () => {
  const open = toggle.getAttribute('aria-expanded') !== 'true';
  toggle.setAttribute('aria-expanded', String(open));
  navigation.classList.toggle('is-open', open);
});
navigation.addEventListener('click', event => { if (event.target.closest('a')) closeMenu(); });
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
    closeMenu(); toggle.focus();
  }
});
matchMedia('(min-width:851px)').addEventListener('change', closeMenu);
document.querySelector('#year').textContent = new Date().getFullYear();
const header = document.querySelector('.header');
function updateHeader() {
  header.classList.toggle('is-scrolled', document.body.classList.contains('inner-page') || window.scrollY >= header.offsetHeight);
}
window.addEventListener('scroll', updateHeader, { passive: true });
window.addEventListener('resize', updateHeader);
updateHeader();

// Auto-rotation stops for deliberate interaction and reduced-motion users.
const hero = document.querySelector('.hero');
if (hero) {
const slides = [...hero.querySelectorAll('.hero-slide')];
const selectors = [...hero.querySelectorAll('[data-slide]')];
const rotationButton = hero.querySelector('.rotation-toggle');
const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
let activeSlide = 0;
let paused = reducedMotion.matches;
let timer;
let pointerInside = false;
let heroVisible = true;
function showSlide(index) {
  activeSlide = (index + slides.length) % slides.length;
  slides.forEach((slide, i) => {
    const active = i === activeSlide;
    slide.classList.toggle('is-active', active);
    slide.inert = !active;
    slide.setAttribute('aria-hidden', String(!active));
    if (active) selectors[i].setAttribute('aria-current', 'true');
    else selectors[i].removeAttribute('aria-current');
  });
}
function scheduleRotation() {
  clearTimeout(timer);
  rotationButton.innerHTML = paused ? 'Play <span aria-hidden="true">▷</span>' : 'Pause <span aria-hidden="true">Ⅱ</span>';
  rotationButton.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
  if (!paused && !pointerInside && !document.hidden && heroVisible) {
    timer = setTimeout(() => { showSlide(activeSlide + 1); scheduleRotation(); }, 5000);
  }
}
selectors.forEach((button, i) => button.addEventListener('click', () => {
  paused = true; showSlide(i); scheduleRotation();
}));
hero.addEventListener('focusin', event => { if (event.target !== rotationButton) { paused = true; scheduleRotation(); } });
rotationButton.addEventListener('click', () => { paused = !paused; scheduleRotation(); });
hero.addEventListener('pointerenter', event => { if (event.pointerType === 'mouse') { pointerInside = true; scheduleRotation(); } });
hero.addEventListener('pointerleave', () => { pointerInside = false; scheduleRotation(); });
document.addEventListener('visibilitychange', scheduleRotation);
reducedMotion.addEventListener('change', () => { paused = reducedMotion.matches; scheduleRotation(); });
new IntersectionObserver(entries => { heroVisible = entries[0].isIntersecting; scheduleRotation(); }).observe(hero);
scheduleRotation();

}
