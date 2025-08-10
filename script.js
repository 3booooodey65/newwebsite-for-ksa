(function(){
  const yearEl = document.getElementById('year');
  if(yearEl){ yearEl.textContent = new Date().getFullYear(); }

  function select(id){ return document.getElementById(id); }

  function isValidSaudiPhone(value){
    if(!value) return false;
    const cleaned = value.replace(/\s|-/g, '');
    return /^(\+?966|0)?5\d{8}$/.test(cleaned);
  }

  function showAlert(containerId, type, message){
    const el = select(containerId);
    if(!el) return;
    el.className = `alert alert-${type}`;
    el.textContent = message;
  }

  // Request form logic
  const requestForm = select('requestForm');
  if(requestForm){
    const fullName = select('fullName');
    const phone = select('phone');
    const address = select('address');
    const deviceType = select('deviceType');
    const issueDescription = select('issueDescription');
    const initialCheck = select('initialCheck');
    const image = select('image');
    const saveDraftBtn = select('saveDraft');

    // Load draft
    try{
      const draft = JSON.parse(localStorage.getItem('requestDraft')||'{}');
      if(draft.fullName) fullName.value = draft.fullName;
      if(draft.phone) phone.value = draft.phone;
      if(draft.address) address.value = draft.address;
      if(draft.deviceType) deviceType.value = draft.deviceType;
      if(draft.issueDescription) issueDescription.value = draft.issueDescription;
      if(typeof draft.initialCheck === 'boolean') initialCheck.checked = draft.initialCheck;
    }catch{}

    function saveDraft(){
      const draft = {
        fullName: fullName.value.trim(),
        phone: phone.value.trim(),
        address: address.value.trim(),
        deviceType: deviceType.value,
        issueDescription: issueDescription.value.trim(),
        initialCheck: !!initialCheck.checked
      };
      localStorage.setItem('requestDraft', JSON.stringify(draft));
      showAlert('request-alert','success','تم حفظ المسودة محلياً.');
    }

    if(saveDraftBtn){ saveDraftBtn.addEventListener('click', saveDraft); }

    function validate(){
      let valid = true;
      if(!isValidSaudiPhone(phone.value)){ valid = false; phone.classList.add('is-invalid'); }
      else { phone.classList.remove('is-invalid'); }
      return valid;
    }

    requestForm.addEventListener('submit', async function(e){
      e.preventDefault();
      if(!validate()){ showAlert('request-alert','danger','يرجى تصحيح رقم الجوال أولاً.'); return; }

      const formData = new FormData(requestForm);
      try{
        const res = await fetch(requestForm.action, { method:'POST', body: formData });
        const data = await res.json();
        if(data.success){
          showAlert('request-alert','success', data.message || 'تم إرسال طلبك بنجاح.');
          localStorage.removeItem('requestDraft');
          requestForm.reset();
        }else{
          showAlert('request-alert','danger', data.message || 'حدث خطأ أثناء الإرسال.');
        }
      }catch(err){
        showAlert('request-alert','danger','تعذر الاتصال بالخادم.');
      }
    });
  }

  // Contact form logic
  const contactForm = select('contactForm');
  if(contactForm){
    const contactPhone = select('contactPhone');
    contactForm.addEventListener('submit', async function(e){
      e.preventDefault();
      if(contactPhone && contactPhone.value && !isValidSaudiPhone(contactPhone.value)){
        contactPhone.classList.add('is-invalid');
        showAlert('contact-alert','danger','يرجى إدخال رقم جوال صحيح.');
        return;
      }
      const formData = new FormData(contactForm);
      try{
        const res = await fetch(contactForm.action, { method:'POST', body: formData });
        const data = await res.json();
        if(data.success){
          showAlert('contact-alert','success', data.message || 'تم إرسال رسالتك بنجاح.');
          contactForm.reset();
        }else{
          showAlert('contact-alert','danger', data.message || 'حدث خطأ أثناء الإرسال.');
        }
      }catch(err){
        showAlert('contact-alert','danger','تعذر الاتصال بالخادم.');
      }
    });
  }
})();