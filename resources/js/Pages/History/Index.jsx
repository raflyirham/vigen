import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function StatusBadge({ status }) {
    const styles = {
        completed: 'border-[#34cf23]/30 bg-[#34cf23]/10 text-[#a8ff9f]',
        script_only: 'border-amber-300/30 bg-amber-300/10 text-amber-100',
        failed_with_fallback: 'border-red-300/30 bg-red-400/10 text-red-100',
        pending: 'border-sky-300/30 bg-sky-300/10 text-sky-100',
        processing: 'border-sky-300/30 bg-sky-300/10 text-sky-100',
    };

    return (
        <span className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-bold ${styles[status] ?? 'border-white/20 bg-white/[0.06] text-white/70'}`}>
            {status.replaceAll('_', ' ')}
        </span>
    );
}

export default function Index({ generations, filters, videoTypes }) {
    const { flash } = usePage().props;
    const [values, setValues] = useState(filters);

    const applyFilters = (event) => {
        event.preventDefault();
        router.get(route('generations.index'), values, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setValues({ search: '', status: '', video_type: '' });
        router.get(route('generations.index'), {}, { replace: true });
    };

    const deleteGeneration = (generation) => {
        if (window.confirm('Delete this generation from your history?')) {
            router.delete(route('generations.destroy', generation.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-bold uppercase text-[#34cf23]">
                            Saved work
                        </p>
                        <h1 className="mt-1 text-3xl font-black text-white">
                            Generation history
                        </h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-white/60">
                            Search, preview, export, or delete your private video outputs.
                        </p>
                    </div>
                    <Link
                        href={route('generator.create')}
                        className="inline-flex min-h-11 items-center justify-center rounded-md bg-[#34cf23] px-4 text-sm font-black text-black transition hover:bg-[#47e237] focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                    >
                        New generation
                    </Link>
                </div>
            }
        >
            <Head title="History" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {flash?.success && (
                    <div className="rounded-md border border-[#34cf23]/30 bg-[#34cf23]/10 px-4 py-3 text-sm text-[#a8ff9f]">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={applyFilters} className="rounded-lg border border-white/10 bg-white/[0.07] p-5 shadow-xl shadow-black/20 backdrop-blur-xl">
                    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_180px_220px_auto]">
                        <TextInput
                            value={values.search}
                            onChange={(event) => setValues({ ...values, search: event.target.value })}
                            placeholder="Search topic, audience, or keywords"
                            className="w-full"
                        />
                        <select
                            value={values.status}
                            onChange={(event) => setValues({ ...values, status: event.target.value })}
                            className="min-h-11 rounded-md border-white/10 bg-[#080b08] text-white shadow-sm focus:border-[#34cf23] focus:ring-[#34cf23]"
                        >
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="script_only">Script only</option>
                            <option value="failed_with_fallback">Fallback</option>
                        </select>
                        <select
                            value={values.video_type}
                            onChange={(event) => setValues({ ...values, video_type: event.target.value })}
                            className="min-h-11 rounded-md border-white/10 bg-[#080b08] text-white shadow-sm focus:border-[#34cf23] focus:ring-[#34cf23]"
                        >
                            <option value="">All video types</option>
                            {videoTypes.map((type) => (
                                <option key={type} value={type}>
                                    {type}
                                </option>
                            ))}
                        </select>
                        <div className="flex gap-3">
                            <button className="min-h-11 rounded-md bg-[#34cf23] px-4 text-sm font-black text-black transition hover:bg-[#47e237] focus:outline-none focus:ring-2 focus:ring-[#34cf23]">
                                Search
                            </button>
                            <SecondaryButton type="button" onClick={clearFilters}>
                                Clear
                            </SecondaryButton>
                        </div>
                    </div>
                </form>

                {generations.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-white/20 bg-white/[0.045] p-10 text-center backdrop-blur">
                        <h2 className="text-xl font-black text-white">
                            No generations found
                        </h2>
                        <p className="mt-2 text-sm leading-6 text-white/60">
                            Create a new video brief or adjust your filters.
                        </p>
                        <Link
                            href={route('generator.create')}
                            className="mt-6 inline-flex min-h-11 items-center rounded-md bg-[#34cf23] px-4 text-sm font-black text-black transition hover:bg-[#47e237]"
                        >
                            Generate now
                        </Link>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {generations.data.map((generation) => (
                            <article key={generation.id} className="rounded-lg border border-white/10 bg-white/[0.06] p-5 shadow-xl shadow-black/20 backdrop-blur-xl">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-xs font-bold uppercase text-white/50">
                                            {generation.video_type}
                                        </p>
                                        <h2 className="mt-2 line-clamp-2 text-lg font-black text-white">
                                            {generation.topic}
                                        </h2>
                                    </div>
                                    <StatusBadge status={generation.status} />
                                </div>
                                <p className="mt-3 text-sm leading-6 text-white/60">
                                    {generation.target_audience} · {generation.requested_duration_seconds}s requested
                                </p>
                                <div className="mt-3 flex items-center justify-between gap-3 text-xs text-white/40">
                                    <span>{generation.created_at}</span>
                                    <span>{generation.has_video ? 'video available' : 'script output'}</span>
                                </div>
                                <div className="mt-5 flex flex-wrap gap-2">
                                    <Link
                                        href={route('generations.show', generation.id)}
                                        className="inline-flex min-h-10 items-center rounded-md bg-[#34cf23] px-3 text-sm font-black text-black transition hover:bg-[#47e237]"
                                    >
                                        Preview
                                    </Link>
                                    <a
                                        href={route('generations.export-script', generation.id)}
                                        className="inline-flex min-h-10 items-center rounded-md border border-white/20 bg-white/[0.05] px-3 text-sm font-semibold text-white transition hover:border-[#34cf23]/50 hover:bg-[#34cf23]/10"
                                    >
                                        Export
                                    </a>
                                    <button
                                        type="button"
                                        onClick={() => deleteGeneration(generation)}
                                        className="inline-flex min-h-10 items-center rounded-md border border-red-300/25 bg-red-400/10 px-3 text-sm font-semibold text-red-100 transition hover:bg-red-400/20"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}

                {generations.links.length > 3 && (
                    <nav className="flex flex-wrap gap-2" aria-label="Pagination">
                        {generations.links.map((link, index) => (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url ?? '#'}
                                preserveScroll
                                className={`rounded-md px-3 py-2 text-sm ${
                                    link.active
                                        ? 'bg-[#34cf23] font-black text-black'
                                        : link.url
                                          ? 'border border-white/20 bg-white/[0.06] text-white/75 hover:text-white'
                                          : 'border border-white/10 bg-white/[0.03] text-white/30'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </nav>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
