<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        You're logged in!
                        <div v-if="$page.props.can && $page.props.can.admin" class="mt-4">
                            <a :href="route('admin.dashboard')" class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mr-2 h-5 w-5"><path d="M9.5 2a.5.5 0 01.5.5V4h1V2.5a.5.5 0 011 0V4h.5A2.5 2.5 0 0115 6.5V8h1.5a.5.5 0 010 1H15v1h1.5a.5.5 0 010 1H15v1.5A2.5 2.5 0 0112.5 15H12v1.5a.5.5 0 01-1 0V15h-1v1.5a.5.5 0 01-1 0V15h-.5A2.5 2.5 0 016 12.5V11H4.5a.5.5 0 010-1H6V9H4.5a.5.5 0 010-1H6V6.5A2.5 2.5 0 018.5 4H9V2.5a.5.5 0 01.5-.5zM8.5 5A1.5 1.5 0 007 6.5V13a1.5 1.5 0 001.5 1.5h5A1.5 1.5 0 0015 13V6.5A1.5 1.5 0 0013.5 5h-5z"/></svg>
                                Go to Administration
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
