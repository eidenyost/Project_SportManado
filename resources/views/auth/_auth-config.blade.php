@include('layouts._assets')

<style>
.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.sports-gradient {
  background:
    radial-gradient(circle at top right, rgba(255, 107, 0, 0.08), transparent),
    radial-gradient(circle at bottom left, rgba(13, 21, 39, 0.8), transparent),
    #080c14;
}
.auth-card {
  background-color: #0d1527;
  border: 1px solid rgba(30, 41, 59, 0.8);
  box-shadow: 0 0 40px rgba(255, 107, 0, 0.05);
}
.group input:focus {
  transform: scale(1.01);
}

@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}
.auth-stagger > * {
  animation: fade-in-up 0.35s ease-out both;
}
.auth-stagger > *:nth-child(1) { animation-delay: 0s; }
.auth-stagger > *:nth-child(2) { animation-delay: 0.05s; }
.auth-stagger > *:nth-child(3) { animation-delay: 0.1s; }
.auth-stagger > *:nth-child(4) { animation-delay: 0.15s; }
.auth-stagger > *:nth-child(5) { animation-delay: 0.2s; }
.auth-stagger > *:nth-child(6) { animation-delay: 0.25s; }
.auth-stagger > *:nth-child(7) { animation-delay: 0.3s; }
</style>
