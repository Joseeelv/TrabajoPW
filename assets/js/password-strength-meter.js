function updatePasswordStrength(password) {
  const strengthMeter = document.querySelector(".password-strength-meter-fill");

  // Evaluar fuerza de la contraseña
  let strength = 0;

  if (password.length >= 8) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/\d/.test(password)) strength++;
  if (/[@$!%*?&/_-]/.test(password)) strength++;

  // Actualizar barra de fuerza
  strengthMeter.style.width = `${Math.min(strength * 20, 100)}%`;

  const colors = ["#ff0000", "#ff4500", "#ffa500", "#9acd32", "#008000"];
  strengthMeter.style.backgroundColor = colors[strength - 1] || "#ddd";

  // Actualizar checklist
  updateChecklistItem(
    "length",
    password.length >= 8,
    `At least 8 characters long (${password.length} characters)`
  );
  updateChecklistItem(
    "uppercase",
    /[A-Z]/.test(password),
    `Contains uppercase letter`,
    password.match(/[A-Z]/g)
  );
  updateChecklistItem(
    "lowercase",
    /[a-z]/.test(password),
    `Contains lowercase letter`,
    password.match(/[a-z]/g)
  );
  updateChecklistItem(
    "number",
    /\d/.test(password),
    `Contains number`,
    password.match(/\d/g)
  );
  updateChecklistItem(
    "special",
    /[@$!%*?&/_-]/.test(password),
    `Contains special character`,
    password.match(/[@$!%*?&/_-]/g)
  );
}

function updateChecklistItem(id, isValid, text, matches = null) {
  const element = document.getElementById(id);
  element.classList.toggle("valid", isValid);
}

// Agregar Event Listener
document.addEventListener("DOMContentLoaded", function () {
  const passwordInput = document.getElementById("password");

  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      updatePasswordStrength(this.value);
    });
  }
});
