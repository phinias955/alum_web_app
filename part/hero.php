
    <!-- Hero Section with Slider -->
    <section class="relative h-screen">
        <!-- Slider container -->
        <div class="slider relative h-full overflow-hidden">
            <!-- Slides -->
            <div class="slides relative h-full">
                <div class="slide absolute inset-0 opacity-0 transition-opacity duration-1000 ease-in-out">
                    <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f" alt="University Campus" class="w-full h-full object-cover">
                </div>
                <div class="slide absolute inset-0 opacity-0 transition-opacity duration-1000 ease-in-out">
                    <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1" alt="Graduation Ceremony" class="w-full h-full object-cover">
                </div>
                <div class="slide absolute inset-0 opacity-0 transition-opacity duration-1000 ease-in-out">
                    <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655" alt="Student Life" class="w-full h-full object-cover">
                </div>
            </div>

            <!-- Overlay -->
            <div class="absolute inset-0 bg-gradient-to-r from-primary/80 to-secondary/80"></div>

            <!-- Content -->
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                        Welcome to AlumniConnect
                    </h1>
                    <p class="mt-3 max-w-md mx-auto text-base text-gray-100 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                        Connect with fellow alumni, access exclusive opportunities, and stay updated with your alma mater.
                    </p>
                    <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                        <div class="rounded-md shadow">
                            <a href="#" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10">
                                Get Started
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slider Navigation -->
            <div class="absolute bottom-5 left-0 right-0 flex justify-center space-x-2">
                <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors slider-nav" data-slide="0"></button>
                <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors slider-nav" data-slide="1"></button>
                <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors slider-nav" data-slide="2"></button>
            </div>
        </div>
    </section>

    <!-- Add slider JavaScript before the closing body tag -->
    <script>
        // Slider functionality
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.slide');
            const navButtons = document.querySelectorAll('.slider-nav');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(index) {
                slides.forEach(slide => slide.style.opacity = '0');
                navButtons.forEach(btn => btn.classList.remove('bg-white'));
                navButtons[index].classList.add('bg-white');
                slides[index].style.opacity = '1';
                currentSlide = index;
            }

            function nextSlide() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }

            // Initialize slider
            showSlide(0);
            slideInterval = setInterval(nextSlide, 5000);

            // Navigation button clicks
            navButtons.forEach((button, index) => {
                button.addEventListener('click', () => {
                    clearInterval(slideInterval);
                    showSlide(index);
                    slideInterval = setInterval(nextSlide, 5000);
                });
            });

            // Pause slider on hover
            const slider = document.querySelector('.slider');
            slider.addEventListener('mouseenter', () => clearInterval(slideInterval));
            slider.addEventListener('mouseleave', () => {
                slideInterval = setInterval(nextSlide, 5000);
            });
        });
    </script>
