<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Alumni Portal - Connect with your fellow graduates">
    <title>Alumni Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#1e3a8a',
                        accent: '#3b82f6'
                    }
                }
            }
        }
    </script>
    <style>
        @media (max-width: 768px) {
            .mobile-menu {
                display: block;
            }
            .desktop-menu {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
<?php include('part/nav.php'); ?>

<?php include('part/hero.php'); ?>

    <!-- Features Section -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Features
                </h2>
            </div>
            <div class="mt-10">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="text-lg font-medium text-gray-900">Alumni Directory</div>
                            <div class="mt-2 text-sm text-gray-500">
                                Connect with graduates from your batch and expand your professional network.
                            </div>
                        </div>
                    </div>
                    <!-- Feature 2 -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="text-lg font-medium text-gray-900">Job Board</div>
                            <div class="mt-2 text-sm text-gray-500">
                                Access exclusive job opportunities posted by fellow alumni and partner companies.
                            </div>
                        </div>
                    </div>
                    <!-- Feature 3 -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="text-lg font-medium text-gray-900">Events</div>
                            <div class="mt-2 text-sm text-gray-500">
                                Stay updated with alumni meetups, workshops, and networking events.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-5 sm:grid-cols-4">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6 text-center">
                        <dt class="text-3xl font-extrabold text-primary">5,000+</dt>
                        <dd class="mt-1 text-gray-500 text-sm">Active Alumni</dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6 text-center">
                        <dt class="text-3xl font-extrabold text-primary">5+</dt>
                        <dd class="mt-1 text-gray-500 text-sm">Monthly Events</dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6 text-center">
                        <dt class="text-3xl font-extrabold text-primary">1,000+</dt>
                        <dd class="mt-1 text-gray-500 text-sm">Job Opportunities</dd>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6 text-center">
                        <dt class="text-3xl font-extrabold text-primary">10+</dt>
                        <dd class="mt-1 text-gray-500 text-sm">Countries</dd>
                    </div>
                </div>
            </div>
        </div>
    </section>

    </section>

    <!-- Latest News Section -->
<?php include('part/news.php'); ?>


    <!-- Upcoming Events Section -->
    <?php include('part/events.php'); ?>

    <!-- Featured Alumni Section -->
  <?php include('part/alumni.php'); ?>

    <!-- Call to Action Section -->
  <?php include('part/cta.php'); ?>

    <!-- Footer -->

        <!-- Footer -->
        <?php include('part/footer.php'); ?>
     <!-- End of Footer -->


    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <script>
        // Mobile menu functionality
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        // Smooth scroll for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    mobileMenu.classList.add('hidden');
                }
            });
        });

        // Scroll to top button visibility
        const scrollToTopBtn = document.querySelector('.scroll-to-top');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 100) {
                scrollToTopBtn.classList.remove('hidden');
            } else {
                scrollToTopBtn.classList.add('hidden');
            }
        });
    </script>
</body>
</html>