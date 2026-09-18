const inquiryForm = document.querySelector('#inquiry-form');
const inquiryStatus = document.querySelector('#inquiry-status');
const inquiryButton = inquiryForm.querySelector('[type="submit"]');
let sendingInquiry = false;
inquiryForm.addEventListener('submit', async event => {
  event.preventDefault();
  if (sendingInquiry || !inquiryForm.reportValidity()) return;
  sendingInquiry = true;
  inquiryButton.disabled = true;
  inquiryButton.textContent = 'Sending…';
  inquiryForm.setAttribute('aria-busy', 'true');
  inquiryStatus.textContent = 'Sending your inquiry…';
  inquiryStatus.dataset.state = 'sending';
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 25000);
  try {
    const response = await fetch(inquiryForm.action, {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(Object.fromEntries(new FormData(inquiryForm))),
      credentials: 'same-origin', signal: controller.signal
    });
    const result = await response.json().catch(() => null);
    if (!response.ok || result?.ok !== true) {
      throw new Error(result?.error || 'We couldn’t send your inquiry. Your message is still here. Please try again or email hello@etherstudios.net.');
    }
    inquiryForm.reset();
    inquiryStatus.dataset.state = 'success';
    inquiryStatus.textContent = 'Thank you. Your inquiry has been received. We’ll be in touch using the email address you provided.';
  } catch (error) {
    inquiryStatus.dataset.state = 'error';
    inquiryStatus.textContent = error.name === 'AbortError' || error instanceof TypeError
      ? 'We couldn’t confirm delivery. Your message is still here. Please email hello@etherstudios.net if you need to confirm receipt.'
      : error.message;
  } finally {
    clearTimeout(timeout);
    sendingInquiry = false;
    inquiryButton.disabled = false;
    inquiryButton.innerHTML = 'Send inquiry <span aria-hidden="true">↗</span>';
    inquiryForm.removeAttribute('aria-busy');
    inquiryStatus.focus();
  }
});
