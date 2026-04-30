import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function StatusBadge({ status }) {
    const styles = {
        completed: 'border-[#34cf23]/30 bg-[#34cf23]/10 text-[#a8ff9f]',
        script_only: 'border-amber-300/30 bg-amber-300/10 text-amber-100',
        failed_with_fallback: 'border-red-300/30 bg-red-400/10 text-red-100',
        pending: 'border-sky-300/30 bg-sky-300/10 text-sky-100',
        processing: 'border-sky-300/30 bg-sky-300/10 text-sky-100',
    };

    return (
        <span className={`inline-flex rounded-full border px-3 py-1 text-sm font-bold ${styles[status] ?? 'border-white/20 bg-white/[0.06] text-white/70'}`}>
            {status.replaceAll('_', ' ')}
        </span>
    );
}

function StoryboardPreview({ generation }) {
    const firstScene = generation.storyboard[0];
    const aspectRatio = generation.aspect_ratio?.replace(':', ' / ') ?? '16 / 9';

    return (
        <div className="w-full rounded-lg border border-white/10 bg-[#080b08] p-4" style={{ aspectRatio }}>
            <div className="flex h-full flex-col justify-between">
                <div className="flex items-center justify-between gap-3">
                    <span className="rounded-md bg-[#34cf23] px-2 py-1 text-xs font-black text-black">
                        STORYBOARD
                    </span>
                    <span className="font-mono text-xs text-white/50">
                        {generation.effective_video_duration_seconds}s preview
                    </span>
                </div>
                <div>
                    <p className="max-w-2xl text-3xl font-black text-white">
                        {firstScene?.on_screen_text ?? generation.topic}
                    </p>
                    <p className="mt-4 max-w-2xl text-sm leading-6 text-white/60">
                        {firstScene?.visual_direction ?? 'The generated storyboard is ready to export.'}
                    </p>
                </div>
                <div className="grid grid-cols-5 gap-2">
                    {generation.storyboard.slice(0, 5).map((scene) => (
                        <div
                            key={scene.scene}
                            className="h-2 rounded-full bg-[#34cf23]"
                            style={{ opacity: 0.35 + scene.scene * 0.1 }}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}

function LoadingPreview({ generation }) {
    const aspectRatio = generation.aspect_ratio?.replace(':', ' / ') ?? '16 / 9';

    return (
        <div
            className="flex w-full items-center justify-center rounded-lg border border-sky-300/20 bg-[#080b08]"
            style={{ aspectRatio }}
        >
            <div className="max-w-md px-6 text-center">
                <div className="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-white/10 border-t-[#34cf23]" />
                <h3 className="mt-5 text-lg font-black text-white">
                    {generation.status === 'pending' ? 'Queued for generation' : 'Generating video'}
                </h3>
                <p className="mt-2 text-sm leading-6 text-white/60">
                    Grok clips are being generated and merged in the background. This page will update automatically.
                </p>
            </div>
        </div>
    );
}

export default function Show({ generation: initialGeneration }) {
    const { flash } = usePage().props;
    const [generation, setGeneration] = useState(initialGeneration);
    const [copied, setCopied] = useState(false);
    const isWorking = ['pending', 'processing'].includes(generation.status);
    const previewAspectRatio = useMemo(
        () => generation.aspect_ratio?.replace(':', ' / ') ?? '16 / 9',
        [generation.aspect_ratio],
    );

    useEffect(() => {
        setGeneration(initialGeneration);
    }, [initialGeneration]);

    useEffect(() => {
        if (!isWorking) {
            return;
        }

        const poll = window.setInterval(async () => {
            try {
                const response = await window.axios.get(route('generations.status', generation.id));
                setGeneration(response.data.generation);
            } catch (error) {
                window.clearInterval(poll);
            }
        }, 3000);

        return () => window.clearInterval(poll);
    }, [generation.id, isWorking]);

    const copyScript = async () => {
        await navigator.clipboard.writeText(generation.script);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 1600);
    };

    const deleteGeneration = () => {
        if (window.confirm('Delete this generation from your history?')) {
            router.delete(route('generations.destroy', generation.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-bold uppercase text-[#34cf23]">
                            Generated output
                        </p>
                        <h1 className="mt-1 max-w-4xl text-3xl font-black text-white">
                            {generation.topic}
                        </h1>
                    </div>
                    <StatusBadge status={generation.status} />
                </div>
            }
        >
            <Head title={generation.topic} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {flash?.success && (
                    <div className="rounded-md border border-[#34cf23]/30 bg-[#34cf23]/10 px-4 py-3 text-sm text-[#a8ff9f]">
                        {flash.success}
                    </div>
                )}

                {generation.error_message && (
                    <div className="rounded-md border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm leading-6 text-amber-100">
                        {generation.error_message}
                    </div>
                )}

                <section className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="rounded-lg border border-white/10 bg-white/[0.07] shadow-2xl shadow-black/20 backdrop-blur-xl">
                        <div className="border-b border-white/10 px-6 py-5">
                            <h2 className="text-xl font-black text-white">
                                Preview
                            </h2>
                            <p className="mt-1 text-sm leading-6 text-white/60">
                                Video output appears here when Grok returns a file; otherwise the storyboard simulation remains export-ready.
                            </p>
                        </div>
                        <div className="p-6">
                            {isWorking ? (
                                <LoadingPreview generation={generation} />
                            ) : generation.preview_url ? (
                                <video
                                    src={generation.preview_url}
                                    controls
                                    className="w-full rounded-lg bg-black"
                                    style={{ aspectRatio: previewAspectRatio }}
                                />
                            ) : (
                                <StoryboardPreview generation={generation} />
                            )}
                        </div>
                    </div>

                    <aside className="rounded-lg border border-white/10 bg-white/[0.06] p-5 shadow-xl shadow-black/20 backdrop-blur-xl">
                        <h2 className="text-base font-black text-white">
                            Brief details
                        </h2>
                        <dl className="mt-4 space-y-3 text-sm">
                            {[
                                ['Type', generation.video_type],
                                ['Audience', generation.target_audience],
                                ['Tone', generation.tone],
                                ['Aspect ratio', generation.aspect_ratio],
                                ['Model', generation.model],
                            ].map(([label, value]) => (
                                <div key={label}>
                                    <dt className="font-semibold text-white/50">
                                        {label}
                                    </dt>
                                    <dd className="mt-1 text-white">{value}</dd>
                                </div>
                            ))}
                            <div>
                                <dt className="font-semibold text-white/50">
                                    Duration
                                </dt>
                                <dd className="mt-1 text-white">
                                    {generation.requested_duration_seconds}s requested · {generation.effective_video_duration_seconds}s output
                                </dd>
                            </div>
                        </dl>

                        <div className="mt-6 grid gap-3">
                            <PrimaryButton type="button" onClick={copyScript}>
                                {copied ? 'Copied' : 'Copy script'}
                            </PrimaryButton>
                            <a
                                className="inline-flex min-h-11 items-center justify-center rounded-md border border-white/20 bg-white/[0.06] px-4 text-sm font-semibold text-white shadow-sm transition hover:border-[#34cf23]/50 hover:bg-[#34cf23]/10 focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                                href={route('generations.export-script', generation.id)}
                            >
                                Export .txt
                            </a>
                            {generation.has_video && (
                                <a
                                    className="inline-flex min-h-11 items-center justify-center rounded-md border border-white/20 bg-white/[0.06] px-4 text-sm font-semibold text-white shadow-sm transition hover:border-[#34cf23]/50 hover:bg-[#34cf23]/10 focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                                    href={route('generations.download-video', generation.id)}
                                >
                                    Download video
                                </a>
                            )}
                            <SecondaryButton type="button" onClick={deleteGeneration}>
                                Delete
                            </SecondaryButton>
                            <Link className="text-center text-sm font-bold text-[#34cf23] hover:text-[#7dff72]" href={route('generations.index')}>
                                Back to history
                            </Link>
                        </div>
                    </aside>
                </section>

                <section className="rounded-lg border border-white/10 bg-white/[0.07] shadow-xl shadow-black/20 backdrop-blur-xl">
                    <div className="border-b border-white/10 px-6 py-5">
                        <h2 className="text-xl font-black text-white">
                            Storyboard
                        </h2>
                    </div>
                    <div className="grid gap-4 p-6 md:grid-cols-2">
                        {generation.storyboard.map((scene) => (
                            <article key={scene.scene} className="rounded-lg border border-white/10 bg-white/[0.045] p-4">
                                <div className="flex items-center justify-between gap-3">
                                    <h3 className="text-sm font-black text-white">
                                        Scene {scene.scene}: {scene.purpose}
                                    </h3>
                                    <span className="font-mono text-xs font-semibold text-[#a8ff9f]">
                                        {scene.timing}
                                    </span>
                                </div>
                                <p className="mt-3 text-sm leading-6 text-white/70">
                                    {scene.visual_direction}
                                </p>
                                <p className="mt-3 text-sm leading-6 text-white/60">
                                    {scene.narration}
                                </p>
                                <p className="mt-3 rounded-md border border-[#34cf23]/20 bg-[#34cf23]/10 px-3 py-2 text-sm font-bold text-[#d8ffd4]">
                                    {scene.on_screen_text}
                                </p>
                            </article>
                        ))}
                    </div>
                </section>

                <section className="rounded-lg border border-white/10 bg-white/[0.07] shadow-xl shadow-black/20 backdrop-blur-xl">
                    <div className="border-b border-white/10 px-6 py-5">
                        <h2 className="text-xl font-black text-white">
                            Script
                        </h2>
                    </div>
                    <pre className="whitespace-pre-wrap p-6 text-sm leading-7 text-white/70">
                        {generation.script}
                    </pre>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
