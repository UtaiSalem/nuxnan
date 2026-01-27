import Swal from 'sweetalert2';

const useSweetAlert = () => {
  const getDarkModeConfig = () => {
    return {
      confirmButtonColor: "#8b5cf6",
      // vikinger-purple
      cancelButtonColor: "#6b7280"
    };
  };
  const success = (message, title = "\u0E2A\u0E33\u0E40\u0E23\u0E47\u0E08!") => {
    return Swal.fire({
      title,
      text: message,
      icon: "success",
      timer: 2500,
      timerProgressBar: true,
      showConfirmButton: false,
      ...getDarkModeConfig()
    });
  };
  const error = (message, title = "\u0E40\u0E01\u0E34\u0E14\u0E02\u0E49\u0E2D\u0E1C\u0E34\u0E14\u0E1E\u0E25\u0E32\u0E14") => {
    return Swal.fire({
      title,
      text: message,
      icon: "error",
      confirmButtonText: "\u0E15\u0E01\u0E25\u0E07",
      ...getDarkModeConfig()
    });
  };
  const warning = (message, title = "\u0E04\u0E33\u0E40\u0E15\u0E37\u0E2D\u0E19") => {
    return Swal.fire({
      title,
      text: message,
      icon: "warning",
      confirmButtonText: "\u0E15\u0E01\u0E25\u0E07",
      ...getDarkModeConfig()
    });
  };
  const info = (message, title = "\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25") => {
    return Swal.fire({
      title,
      text: message,
      icon: "info",
      confirmButtonText: "\u0E15\u0E01\u0E25\u0E07",
      ...getDarkModeConfig()
    });
  };
  const confirm = async (message, title = "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23", options = {}) => {
    const {
      confirmText = "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19",
      cancelText = "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
      icon = "question",
      isDanger = false
    } = options;
    const result = await Swal.fire({
      title,
      text: message,
      icon,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      confirmButtonColor: isDanger ? "#ef4444" : "#8b5cf6",
      reverseButtons: true,
      focusCancel: isDanger,
      ...getDarkModeConfig(),
      // Override confirm button color for danger actions
      ...isDanger && { confirmButtonColor: "#ef4444" }
    });
    return result.isConfirmed;
  };
  const confirmDelete = async (itemName = "\u0E23\u0E32\u0E22\u0E01\u0E32\u0E23\u0E19\u0E35\u0E49", additionalMessage = "") => {
    const message = additionalMessage ? `\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A${itemName}\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?

${additionalMessage}` : `\u0E04\u0E38\u0E13\u0E15\u0E49\u0E2D\u0E07\u0E01\u0E32\u0E23\u0E25\u0E1A${itemName}\u0E2B\u0E23\u0E37\u0E2D\u0E44\u0E21\u0E48?`;
    return confirm(message, "\u0E22\u0E37\u0E19\u0E22\u0E31\u0E19\u0E01\u0E32\u0E23\u0E25\u0E1A", {
      confirmText: "\u0E25\u0E1A",
      cancelText: "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
      icon: "warning",
      isDanger: true
    });
  };
  const toast = (message, icon = "success") => {
    const Toast = Swal.mixin({
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 3e3,
      timerProgressBar: true,
      didOpen: (toast2) => {
        toast2.onmouseenter = Swal.stopTimer;
        toast2.onmouseleave = Swal.resumeTimer;
      },
      ...getDarkModeConfig()
    });
    return Toast.fire({
      icon,
      title: message
    });
  };
  const showLoading = (message = "\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23...") => {
    return Swal.fire({
      title: message,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      },
      ...getDarkModeConfig()
    });
  };
  const close = () => {
    Swal.close();
  };
  const input = async (title, options = {}) => {
    const {
      inputType = "text",
      placeholder = "",
      inputValue = "",
      confirmText = "\u0E15\u0E01\u0E25\u0E07",
      cancelText = "\u0E22\u0E01\u0E40\u0E25\u0E34\u0E01",
      inputValidator
    } = options;
    const result = await Swal.fire({
      title,
      input: inputType,
      inputPlaceholder: placeholder,
      inputValue,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      reverseButtons: true,
      inputValidator: inputValidator ? (value) => {
        return inputValidator(value);
      } : void 0,
      ...getDarkModeConfig()
    });
    return result.isConfirmed ? result.value : null;
  };
  return {
    success,
    error,
    warning,
    info,
    confirm,
    confirmDelete,
    toast,
    showLoading,
    close,
    input,
    // Expose raw Swal for advanced usage
    Swal
  };
};

export { useSweetAlert as u };
//# sourceMappingURL=useSweetAlert-jHixiibP.mjs.map
