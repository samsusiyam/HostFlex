const all_password = document.querySelectorAll("input[type=password]");
const all_password_toggle_btns = document.querySelectorAll(".toggle-password");
all_password_toggle_btns.forEach((btn) => {
    btn.addEventListener("click", (e) => {
        const btn_target = btn?.dataset?.target;
        all_password.forEach((input) => {
            const input_target = input?.dataset?.target;
            if (btn_target === input_target) {
                if (input?.type === "password") {
                    input.type = "text";
                    btn.innerHTML = `<i class="fa-regular fa-eye"></i>`;
                } else {
                    input.type = "password";
                    btn.innerHTML = `<i class="fa-regular fa-eye-slash"></i>`;
                }
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
        const all_error_messages = document.querySelectorAll(".error_message");
        all_error_messages.forEach((m) => (m.style.display = "none"));
    }, 20000);
    const all_toast_messsages = document.querySelectorAll(".toast-message");
    all_toast_messsages.forEach((ele) => {
        setTimeout(() => {
            ele.style.display = "none";
        }, 3000);
    });
});

const all_servicePackage = document.querySelectorAll(".servicePackage");
all_servicePackage.forEach((ele) => {
    ele.addEventListener("click", () => {
        document.getElementById("selectedIndex").setAttribute("value", ele?.dataset?.index);
        all_servicePackage.forEach((ele2) => ele2.classList.remove("active"));
        ele.classList.add("active");
    });
});

const all_tutorial = document.querySelectorAll(".tutorial");
const all_tutorial_btn = document.querySelectorAll(".tutorial_btn");
all_tutorial_btn.forEach((btn) => {
    btn.addEventListener("click", () => {
        all_tutorial_btn.forEach((btn2) => {
            btn2.classList.remove("btn-active");
            btn2.classList.add("btn-inactive");
        });
        btn.classList.remove("btn-inactive");
        btn.classList.add("btn-active");
        const btn_category = btn?.dataset?.category?.toLowerCase();
        if (btn_category === "all") {
            all_tutorial.forEach((ele) => ele.classList.remove("hidden"));
        } else {
            all_tutorial.forEach((ele) => {
                const div_category = ele?.dataset?.category?.toLowerCase();
                if (btn_category === div_category) {
                    ele.classList.remove("hidden");
                } else {
                    ele.classList.add("hidden");
                }
            });
        }
    });
});

// Mobile Nav Toggle with Smooth Scale & Fade Animation
(() => {
    const mobile_nav = document.getElementById("mobile-nav");
    const mobile_nav_toggle = document.getElementById("mobile-nav-toggle");
    let open_nav = false;
    if (mobile_nav_toggle && mobile_nav) {
        mobile_nav_toggle.addEventListener("click", function (e) {
            e.stopPropagation();
            open_nav = !open_nav;
            if (open_nav) {
                mobile_nav_toggle.innerHTML = '<i class="fa fa-times"></i>';
                mobile_nav.style.transform = "scaleY(1)";
                mobile_nav.style.opacity = "1";
                mobile_nav.style.pointerEvents = "auto";
            } else {
                mobile_nav_toggle.innerHTML = '<i class="fa fa-bars"></i>';
                mobile_nav.style.transform = "scaleY(0)";
                mobile_nav.style.opacity = "0";
                mobile_nav.style.pointerEvents = "none";
            }
        });
        document.addEventListener("click", function (e) {
            if (open_nav && !mobile_nav.contains(e.target) && !mobile_nav_toggle.contains(e.target)) {
                open_nav = false;
                mobile_nav_toggle.innerHTML = '<i class="fa fa-bars"></i>';
                mobile_nav.style.transform = "scaleY(0)";
                mobile_nav.style.opacity = "0";
                mobile_nav.style.pointerEvents = "none";
            }
        });
    }
})();

// Accordion Submenu Toggle (Auto-closes other submenus)
window.toggleMobileAccordion = function(btn) {
    const submenu = btn.nextElementSibling;
    const icon = btn.querySelector('.fa-chevron-down');
    const isCurrentlyOpen = submenu && !submenu.classList.contains('hidden');
    
    // Close all open submenus
    document.querySelectorAll('.mobile-submenu').forEach(function(sm) {
        sm.classList.add('hidden');
        sm.classList.remove('flex');
    });
    document.querySelectorAll('.mobile-menu-item .fa-chevron-down').forEach(function(i) {
        i.style.transform = 'rotate(0deg)';
    });
    
    // Open this submenu if it was closed
    if (!isCurrentlyOpen && submenu) {
        submenu.classList.remove('hidden');
        submenu.classList.add('flex');
        if (icon) {
            icon.style.transform = 'rotate(180deg)';
        }
    }
};

const pricingSwitch = document.getElementById("pricingSwitch");
if (pricingSwitch) {
    pricingSwitch.addEventListener("change", (e) => {
        const isAnnual = e.target?.checked;
        const cards = document.querySelectorAll('.plan-pricing-card');
        if (cards.length > 0) {
            cards.forEach(card => {
                const mode = card.dataset.billingMode;
                const pVal = card.querySelector('.priceValue');
                const pFor = card.querySelector('.priceFor');
                if (!pVal || !pFor) return;

                if (mode === 'yearly-only') {
                    pVal.innerHTML = pVal.dataset.yearly;
                    pFor.innerHTML = ' /Year';
                } else if (mode === 'monthly-only') {
                    pVal.innerHTML = pVal.dataset.monthly;
                    pFor.innerHTML = ' /Month';
                } else {
                    if (isAnnual) {
                        pVal.innerHTML = pVal.dataset.yearly;
                        pFor.innerHTML = ' /Year';
                    } else {
                        pVal.innerHTML = pVal.dataset.monthly;
                        pFor.innerHTML = ' /Month';
                    }
                }
            });
        } else {
            const all_priceValue = document.querySelectorAll(".priceValue");
            const all_priceFor = document.querySelectorAll(".priceFor");
            for (let i = 0; i < all_priceFor.length; i++) {
                const priceValue = all_priceValue[i];
                const priceFor = all_priceFor[i];
                if (isAnnual) {
                    priceFor.innerHTML = ` /Year`;
                    priceValue.innerHTML = priceValue?.dataset?.yearly;
                } else {
                    priceFor.innerHTML = ` /Month`;
                    priceValue.innerHTML = priceValue?.dataset?.monthly;
                }
            }
        }
    });
}