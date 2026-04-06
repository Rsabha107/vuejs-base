import Swal from "sweetalert2";

// Global SweetAlert config
export default window.Swal = Swal.mixin({
    customClass: {
        confirmButton: "btn btn-danger me-2",
        cancelButton: "btn btn-secondary",
    },
    buttonsStyling: false,
    showClass: {
        popup: "animate__animated animate__fadeInDown",
    },
    hideClass: {
        popup: "animate__animated animate__fadeOutUp",
    },
});