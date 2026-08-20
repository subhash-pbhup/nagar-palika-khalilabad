<footer class="text-center text-gray-500 text-base mt-12">
    © 2025 Khalilabad Nagar Parishad - All Rights Reserved.
</footer>
<script>
    document.addEventListener("DOMContentLoaded", function() {


        const profileBtn =
            document.getElementById("profileBtn");


        const profileMenu =
            document.getElementById("profileMenu");


        const profileArrow =
            document.getElementById("profileArrow");


        // Safety check

        if (!profileBtn || !profileMenu) {
            return;
        }


        // =========================================================
        // PROFILE BUTTON CLICK
        // =========================================================

        profileBtn.addEventListener(
            "click",
            function(event) {

                event.stopPropagation();


                const isHidden =
                    profileMenu.classList.contains("hidden");


                if (isHidden) {

                    // Open
                    profileMenu.classList.remove("hidden");


                    if (profileArrow) {

                        profileArrow.classList.add(
                            "rotate-180"
                        );

                    }

                } else {

                    // Close
                    profileMenu.classList.add("hidden");


                    if (profileArrow) {

                        profileArrow.classList.remove(
                            "rotate-180"
                        );

                    }

                }

            }
        );


        // =========================================================
        // DROPDOWN CLICK
        // =========================================================

        profileMenu.addEventListener(
            "click",
            function(event) {

                event.stopPropagation();

            }
        );


        // =========================================================
        // OUTSIDE CLICK
        // =========================================================

        document.addEventListener(
            "click",
            function() {

                profileMenu.classList.add(
                    "hidden"
                );


                if (profileArrow) {

                    profileArrow.classList.remove(
                        "rotate-180"
                    );

                }

            }
        );


        // =========================================================
        // ESC KEY
        // =========================================================

        document.addEventListener(
            "keydown",
            function(event) {

                if (event.key === "Escape") {

                    profileMenu.classList.add(
                        "hidden"
                    );


                    if (profileArrow) {

                        profileArrow.classList.remove(
                            "rotate-180"
                        );

                    }

                }

            }
        );

    });
</script>
</main>
</body>

</html>