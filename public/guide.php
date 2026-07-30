<?php include 'layouts/header.php'; ?>
<?php include 'layouts/sidebar.php'; ?>

<div class="content-wrapper">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary-container": "#094cb2",
                        "on-error": "#ffffff",
                        "primary": "#003686",
                        "secondary": "#00639d",
                        "on-error-container": "#93000a",
                        "on-tertiary-container": "#c4c5c6",
                        "on-secondary": "#ffffff",
                        "tertiary-fixed": "#e1e3e4",
                        "on-background": "#121c28",
                        "tertiary-fixed-dim": "#c5c7c8",
                        "surface-container-low": "#eef4ff",
                        "primary-fixed": "#dae2ff",
                        "outline": "#737784",
                        "inverse-on-surface": "#eaf1ff",
                        "surface-container-highest": "#d9e3f4",
                        "surface-bright": "#f8f9ff",
                        "surface-container": "#e5eeff",
                        "primary-fixed-dim": "#b1c5ff",
                        "on-secondary-container": "#00385c",
                        "surface-tint": "#2259bf",
                        "error": "#ba1a1a",
                        "background": "#f8f9ff",
                        "secondary-fixed-dim": "#98cbff",
                        "secondary-container": "#1ba4fe",
                        "inverse-surface": "#27313e",
                        "outline-variant": "#c3c6d5",
                        "on-secondary-fixed": "#001d33",
                        "on-secondary-fixed-variant": "#004a77",
                        "inverse-primary": "#b1c5ff",
                        "on-primary-container": "#b0c5ff",
                        "on-surface": "#121c28",
                        "surface-container-lowest": "#ffffff",
                        "on-tertiary-fixed-variant": "#454748",
                        "surface-container-high": "#dfe9fa",
                        "on-tertiary": "#ffffff",
                        "on-primary-fixed-variant": "#00419e",
                        "on-primary-fixed": "#001946",
                        "secondary-fixed": "#cfe5ff",
                        "error-container": "#ffdad6",
                        "on-surface-variant": "#434653",
                        "on-tertiary-fixed": "#191c1d",
                        "tertiary": "#393b3c",
                        "surface": "#f8f9ff",
                        "surface-variant": "#d9e3f4",
                        "on-primary": "#ffffff",
                        "tertiary-container": "#505253",
                        "surface-dim": "#d1dbec"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "stack-md": "16px",
                        "stack-lg": "24px",
                        "gutter": "16px",
                        "container-padding": "24px",
                        "stack-sm": "8px",
                        "sidebar-width": "260px"
                    },
                    "fontFamily": {
                        "label-md": ["Inter"],
                        "body-sm": ["Inter"],
                        "body-md": ["Inter"],
                        "display-lg": ["Inter"],
                        "body-lg": ["Inter"],
                        "mono-label": ["Inter"],
                        "headline-md": ["Inter"],
                        "headline-sm": ["Inter"]
                    },
                    "fontSize": {
                        "label-md": ["12px", {
                            "lineHeight": "16px",
                            "letterSpacing": "0.02em",
                            "fontWeight": "600"
                        }],
                        "body-sm": ["13px", {
                            "lineHeight": "18px",
                            "fontWeight": "400"
                        }],
                        "body-md": ["14px", {
                            "lineHeight": "20px",
                            "fontWeight": "400"
                        }],
                        "display-lg": ["32px", {
                            "lineHeight": "40px",
                            "letterSpacing": "-0.02em",
                            "fontWeight": "700"
                        }],
                        "body-lg": ["16px", {
                            "lineHeight": "24px",
                            "fontWeight": "400"
                        }],
                        "mono-label": ["12px", {
                            "lineHeight": "16px",
                            "fontWeight": "500"
                        }],
                        "headline-md": ["24px", {
                            "lineHeight": "32px",
                            "letterSpacing": "-0.01em",
                            "fontWeight": "600"
                        }],
                        "headline-sm": ["20px", {
                            "lineHeight": "28px",
                            "fontWeight": "600"
                        }]
                    }
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }

        .scroll-mt-24 {
            scroll-margin-top: 6rem;
        }

        .toc-active {
            color: #003686;
            font-weight: 600;
            border-left-color: #003686;
        }

        /* Tailwind's `collapse` utility conflicts with Bootstrap's sidebar
           collapse component. Keep the Bootstrap menus visible when open. */
        .sidebar .collapse.show,
        .sidebar .collapsing {
            visibility: visible !important;
        }

        .guide-toc {
            display: none;
        }

        @media (min-width: 1280px) {
            .guide-main {
                margin-right: 304px;
            }

            .guide-toc {
                display: block;
                position: fixed;
                top: 88px;
                right: 24px;
                bottom: 24px;
                width: 280px;
                overflow-y: auto;
            }
        }
    </style>

    <div class="bg-background text-on-surface">
        <!-- Main Content Canvas -->
        <main class="flex flex-1 overflow-visible">
            <!-- Content Area -->
            <div class="guide-main flex-1 p-0 overflow-y-auto">
                <section class="mb-16 scroll-mt-24" id="introduction">
                    <h1 class="ams-page-title mb-1">User Guide &amp; Operations Manual</h1>
                    <p class="text-body-lg text-on-surface-variant leading-relaxed">
                        Welcome to BROWAVE Accommodation Management System (AMS) — a centralized platform designed to simplify employee accommodation management, room assignments, and daily monitoring.
                        This guide will help administrators and authorized users efficiently operate the system by providing step-by-step instructions for every module and feature.
                    </p>
                </section>
                <section class="mb-10 scroll-mt-24" id="departments">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded bg-primary-fixed flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary" data-icon="business">business</span>
                        </div>
                        <h2 class="text-headline-md font-headline-md text-on-surface">Department &amp; Employees</h2>
                    </div>
                    <div class="space-y-10">
                        <div class="bg-white border border-outline-variant rounded-xl overflow-hidden">
                            <div class="p-6 border-b border-outline-variant bg-surface-container-lowest">
                                <h3 class="text-headline-sm font-headline-sm">1. Creating Departments</h3>
                                <p class="text-body-sm text-on-surface-variant">Departments are the root nodes of your organization.</p>
                            </div>
                            <div class="p-6">
                                <ol class="space-y-4">
                                    <li class="flex gap-4">
                                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm">1</span>
                                        <div>
                                            <p class="font-bold text-on-surface">Navigate to Administration</p>
                                            <p class="text-body-md text-on-surface-variant">Select the 'Departments' tab from the main sidebar.</p>
                                        </div>
                                    </li>
                                    <li class="flex gap-4">
                                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-sm">2</span>
                                        <div>
                                            <p class="font-bold text-on-surface">Click 'Add New'</p>
                                            <p class="text-body-md text-on-surface-variant">Input the Department Name.</p>
                                        </div>
                                    </li>
                                </ol>
                            </div>
                        </div>
                        <div class="bg-white border border-outline-variant rounded-xl overflow-hidden">
                            <div class="p-6 border-b border-outline-variant bg-surface-container-lowest">
                                <h3 class="text-headline-sm font-headline-sm">2. Bulk-Upload Workflow</h3>
                                <p class="text-body-sm text-on-surface-variant">Process high volumes of personnel data efficiently.</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-secondary" data-icon="file_download">file_download</span>
                                        <p class="text-body-md"><span class="font-bold">Step 1:</span> Download the official CSV/Excel template from the Employee Dashboard.</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-secondary" data-icon="edit_note">edit_note</span>
                                        <p class="text-body-md"><span class="font-bold">Step 2:</span> Populate fields ensuring the "Department Code" matches exactly with existing entries.</p>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="material-symbols-outlined text-secondary" data-icon="cloud_upload">cloud_upload</span>
                                        <p class="text-body-md"><span class="font-bold">Step 3:</span> Upload the file. The system will perform a validation check for duplicate IDs.</p>
                                    </div>
                                </div>
                                <div class="bg-surface-container p-4 rounded-lg flex items-center justify-center border border-dashed border-outline">
                                    <div class="text-center">
                                        <span class="material-symbols-outlined text-4xl text-outline mb-2" data-icon="csv">csv</span>
                                        <p class="text-label-md">Drag &amp; Drop Area Simulation</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="mb-20 scroll-mt-24" id="accommodation">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded bg-primary-fixed flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary" data-icon="hotel">hotel</span>
                        </div>
                        <h2 class="text-headline-md font-headline-md text-on-surface">Accommodation &amp; Rooms</h2>
                    </div>
                    <div class="bg-white border border-outline-variant rounded-xl p-8">
                        <h3 class="text-headline-sm font-headline-sm mb-6">The Infrastructure Hierarchy</h3>
                        <div class="flex items-center justify-between mb-10 relative px-4">
                            <div class="absolute top-1/2 left-0 w-full h-[2px] bg-outline-variant -z-10"></div>
                            <div class="flex flex-col items-center gap-2 bg-white px-4">
                                <div class="w-14 h-14 rounded-full border-2 border-primary flex items-center justify-center bg-primary-fixed">
                                    <span class="material-symbols-outlined text-primary" data-icon="domain">domain</span>
                                </div>
                                <span class="text-label-md text-primary">Accommodation</span>
                            </div>
                            <div class="flex flex-col items-center gap-2 bg-white px-4">
                                <div class="w-14 h-14 rounded-full border-2 border-primary flex items-center justify-center bg-primary-fixed">
                                    <span class="material-symbols-outlined text-primary" data-icon="apartment">apartment</span>
                                </div>
                                <span class="text-label-md text-primary">Building</span>
                            </div>
                            <div class="flex flex-col items-center gap-2 bg-white px-4">
                                <div class="w-14 h-14 rounded-full border-2 border-primary flex items-center justify-center bg-primary-fixed">
                                    <span class="material-symbols-outlined text-primary" data-icon="layers">layers</span>
                                </div>
                                <span class="text-label-md text-primary">Floor</span>
                            </div>
                            <div class="flex flex-col items-center gap-2 bg-white px-4">
                                <div class="w-14 h-14 rounded-full border-2 border-primary flex items-center justify-center bg-primary-fixed">
                                    <span class="material-symbols-outlined text-primary" data-icon="meeting_room">meeting_room</span>
                                </div>
                                <span class="text-label-md text-primary">Room</span>
                            </div>
                        </div>
                        <div class="bg-surface-container-low p-6 rounded-lg border-l-4 border-primary">
                            <h4 class="font-bold text-primary mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined" data-icon="bolt">bolt</span>
                                Featured Tool: Generate Multiple Rooms
                            </h4>
                            <p class="text-body-md text-on-surface-variant">
                                Located within the 'Floor' view, this tool allows you to create hundreds of rooms simultaneously. Define a prefix (e.g., "A-"), a starting number (e.g., 101), and the increment. You can also bulk-apply attributes like "En-suite" or "Queen Bed" to the entire set.
                            </p>
                        </div>
                    </div>
                </section>
                <section class="mb-20 scroll-mt-24" id="logistics">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded bg-primary-fixed flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary" data-icon="local_shipping">local_shipping</span>
                        </div>
                        <h2 class="text-headline-md font-headline-md text-on-surface">Logistics Modules</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-white border border-outline-variant rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-headline-sm font-headline-sm">Company Car</h3>
                                <span class="text-label-md bg-secondary-container text-on-secondary-container px-2 py-1 rounded">Fleet Management</span>
                            </div>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-2 text-body-md">
                                    <span class="material-symbols-outlined text-primary text-sm" data-icon="check_circle">check_circle</span>
                                    <span><span class="font-bold">Active Tab:</span> Manage currently assigned vehicles.</span>
                                </li>
                                <li class="flex items-start gap-2 text-body-md">
                                    <span class="material-symbols-outlined text-primary text-sm" data-icon="check_circle">check_circle</span>
                                    <span><span class="font-bold">Archive Tab:</span> History of completed transportation records.</span>
                                </li>
                            </ul>
                        </div>
                        <div class="bg-white border border-outline-variant rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-headline-sm font-headline-sm">Meals &amp; Catering</h3>
                                <span class="text-label-md bg-secondary-container text-on-secondary-container px-2 py-1 rounded">Automation</span>
                            </div>
                            <p class="text-body-md text-on-surface-variant mb-4">
                                The headcount logic is <span class="italic">Automatic</span>. By syncing with Room Assignments and Shift Rotations, the system generates real-time kitchen reports.
                            </p>
                            <div class="flex items-center gap-2 text-primary font-bold text-body-sm">
                                <span class="material-symbols-outlined" data-icon="info">info</span>
                                No manual tallying required.
                            </div>
                        </div>
                    </div>
                </section>
                <section class="mb-20 scroll-mt-24" id="assignments">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded bg-primary-fixed flex items-center justify-center">
                            <span class="material-symbols-outlined text-primary" data-icon="assignment_ind">assignment_ind</span>
                        </div>
                        <h2 class="text-headline-md font-headline-md text-on-surface">Room Assignments</h2>
                    </div>
                    <div class="space-y-6">
                        <div class="flex gap-6">
                            <div class="flex-1 bg-white border border-outline-variant rounded-xl p-6">
                                <h4 class="font-bold mb-3">Assign Flow</h4>
                                <p class="text-body-sm text-on-surface-variant mb-4">Assign an unallocated employee to a vacant room.</p>
                                <div class="space-y-2 text-body-sm">
                                    <div class="p-2 bg-surface-container rounded border border-outline-variant">Select Employee</div>
                                    <div class="flex justify-center"><span class="material-symbols-outlined text-outline" data-icon="arrow_downward">arrow_downward</span></div>
                                    <div class="p-2 bg-surface-container rounded border border-outline-variant">Filter Available Rooms</div>
                                    <div class="flex justify-center"><span class="material-symbols-outlined text-outline" data-icon="arrow_downward">arrow_downward</span></div>
                                    <div class="p-2 bg-primary-container text-white rounded font-bold">Confirm &amp; Date Entry</div>
                                </div>
                            </div>
                            <div class="flex-1 bg-white border border-outline-variant rounded-xl p-6">
                                <h4 class="font-bold mb-3">Transfer Flow</h4>
                                <p class="text-body-sm text-on-surface-variant mb-4">Move a resident to a different room or building.</p>
                                <div class="space-y-2 text-body-sm">
                                    <div class="p-2 bg-surface-container rounded border border-outline-variant">View Resident Profile</div>
                                    <div class="flex justify-center"><span class="material-symbols-outlined text-outline" data-icon="arrow_downward">arrow_downward</span></div>
                                    <div class="p-2 bg-surface-container rounded border border-outline-variant">Select 'Transfer' Action</div>
                                    <div class="flex justify-center"><span class="material-symbols-outlined text-outline" data-icon="arrow_downward">arrow_downward</span></div>
                                    <div class="p-2 bg-primary-container text-white rounded font-bold">Swap Rooms (Auto-Checkout)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="mb-20 scroll-mt-24" id="rules">
                    <div class="bg-error-container text-on-error-container p-8 rounded-2xl border-2 border-error">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="material-symbols-outlined text-3xl" data-icon="warning">warning</span>
                            <h2 class="text-headline-md font-headline-md">Important: Rules &amp; Requirements</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h4 class="font-bold mb-2">Critical Dependencies</h4>
                                <ul class="space-y-2">
                                    <li class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-lg" data-icon="priority_high">priority_high</span>
                                        <span class="text-body-md">Departments MUST be created before Rooms can be mapped.</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-lg" data-icon="priority_high">priority_high</span>
                                        <span class="text-body-md">Accommodation assets MUST have at least one Building.</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-lg" data-icon="priority_high">priority_high</span>
                                        <span class="text-body-md">Employees cannot be deleted if they have an active assignment.</span>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-bold mb-2">Operational Integrity</h4>
                                <p class="text-body-md mb-4">The AMS enforces strict data integrity. Deleting a 'Department' will fail if any employees are still associated with it. Transfer them first.</p>
                                <button class="bg-on-error-container text-white px-4 py-2 rounded-lg text-label-md font-bold hover:opacity-90 transition-all">Download Safety Manual (PDF)</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <!-- Table of Contents Sticky Sub-nav -->
            <aside class="guide-toc bg-white border-l border-outline-variant p-8">
                <h4 class="text-label-md font-bold text-on-surface-variant uppercase tracking-wider mb-6">In this guide</h4>
                <nav class="space-y-1">
                    <a class="block py-2 px-3 text-body-sm text-on-surface-variant hover:bg-surface-container rounded transition-colors border-l-2 border-transparent" href="#introduction">Introduction</a>
                    <a class="block py-2 px-3 text-body-sm text-on-surface-variant hover:bg-surface-container rounded transition-colors border-l-2 border-transparent" href="#departments">Department &amp; Employees</a>
                    <a class="block py-2 px-3 text-body-sm text-on-surface-variant hover:bg-surface-container rounded transition-colors border-l-2 border-transparent" href="#accommodation">Accommodation &amp; Rooms</a>
                    <a class="block py-2 px-3 text-body-sm text-on-surface-variant hover:bg-surface-container rounded transition-colors border-l-2 border-transparent" href="#logistics">Logistics Modules</a>
                    <a class="block py-2 px-3 text-body-sm text-on-surface-variant hover:bg-surface-container rounded transition-colors border-l-2 border-transparent" href="#assignments">Room Assignments</a>
                    <a class="block py-2 px-3 text-body-sm text-on-surface-variant hover:bg-surface-container rounded transition-colors border-l-2 border-transparent" href="#rules">Important Notes</a>
                </nav>
                <div class="mt-12 bg-primary-fixed p-4 rounded-lg">
                    <p class="text-label-md font-bold text-primary mb-2">Need Help?</p>
                    <p class="text-body-sm text-primary/80 mb-4">Contact our 24/7 Enterprise Support team for complex migration assistance.</p>
                    <button class="w-full py-2 bg-primary text-white rounded-lg text-label-md font-bold">Chat Support</button>
                </div>
            </aside>
        </main>
    </div>
    <script>
        // Simple Scroll Spy for ToC highlighting
        const sections = document.querySelectorAll('section');
        const tocLinks = document.querySelectorAll('aside nav a');

        window.addEventListener('scroll', () => {
            let current = "";
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            tocLinks.forEach(link => {
                link.classList.remove('toc-active');
                if (link.getAttribute('href').includes(current)) {
                    link.classList.add('toc-active');
                }
            });
        });

        // Hover scale effect for buttons
        document.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('mousedown', () => btn.classList.add('scale-95'));
            btn.addEventListener('mouseup', () => btn.classList.remove('scale-95'));
            btn.addEventListener('mouseleave', () => btn.classList.remove('scale-95'));
        });
    </script>
</div>
</div>