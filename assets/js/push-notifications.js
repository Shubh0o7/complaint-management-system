(() => {
  const button = document.getElementById('enablePushNotifications');
  const status = document.getElementById('pushSubscriptionStatus');
  if (!button || !status) return;

  const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
  const setStatus = (message, danger = false) => {
    status.textContent = message;
    status.classList.toggle('text-danger', danger);
    status.classList.toggle('text-success', !danger);
  };

  const urlBase64ToUint8Array = (value) => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
  };

  button.addEventListener('click', async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
      setStatus('This browser does not support push alerts.', true);
      return;
    }
    try {
      button.disabled = true;
      setStatus('Requesting browser permission…');
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') throw new Error('Browser permission was not granted.');
      const registration = await navigator.serviceWorker.register('/service-worker.js');
      const publicKey = document.querySelector('meta[name="vapid-public-key"]')?.content || '';
      if (!publicKey) throw new Error('Push provider is not configured yet.');
      let subscription = await registration.pushManager.getSubscription();
      if (!subscription) subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(publicKey) });
      const response = await fetch('api/push_subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ endpoint: subscription.endpoint, keys: subscription.toJSON().keys })
      });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Unable to save subscription.');
      setStatus(result.configured ? 'Browser push alerts enabled.' : 'Subscription saved; provider setup is still required.');
    } catch (error) {
      setStatus(error.message || 'Unable to enable browser alerts.', true);
    } finally {
      button.disabled = false;
    }
  });
})();
