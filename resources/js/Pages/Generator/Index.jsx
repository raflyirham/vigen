import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

const blankForm = {
    video_type: 'marketing video',
    topic: '',
    keywords: '',
    target_audience: '',
    tone: 'persuasive',
    aspect_ratio: '16:9',
    duration: 30,
    template_key: 'marketing',
};

const videoTypes = [
    'marketing video',
    'educational clip',
    'social media reel',
    'product demo',
    'announcement',
];

const tones = ['persuasive', 'formal', 'casual', 'clear', 'excited', 'practical'];
const aspectRatios = ['16:9', '9:16', '1:1', '2:3', '3:2'];

function StatusBadge({ status }) {
    const styles = {
        completed: 'border-[#34cf23]/30 bg-[#34cf23]/10 text-[#a8ff9f]',
        script_only: 'border-amber-300/30 bg-amber-300/10 text-amber-100',
        failed_with_fallback: 'border-red-300/30 bg-red-400/10 text-red-100',
        pending: 'border-sky-300/30 bg-sky-300/10 text-sky-100',
        processing: 'border-sky-300/30 bg-sky-300/10 text-sky-100',
    };

    return (
        <span
            className={`inline-flex rounded-full border px-2.5 py-1 text-xs font-bold ${styles[status] ?? 'border-white/20 bg-white/[0.06] text-white/70'}`}
        >
            {status.replaceAll('_', ' ')}
        </span>
    );
}

function FieldShell({ children }) {
    return <div className="space-y-2">{children}</div>;
}

export default function Index({ templates, recentGenerations, grokConfigured }) {
    const { flash } = usePage().props;
    const templateOptions = useMemo(() => Object.entries(templates), [templates]);
    const { data, setData, post, processing, errors, reset } = useForm(blankForm);

    const applyTemplate = (key) => {
        const template = templates[key];
        setData({
            ...data,
            template_key: key,
            video_type: template?.video_type ?? data.video_type,
            tone: template?.tone ?? data.tone,
        });
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('generations.store'), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-bold uppercase text-[#34cf23]">
                            Dashboard
                        </p>
                        <h1 className="mt-1 text-3xl font-black text-white">
                            Generate video content
                        </h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/60">
                            Create a saved script, storyboard, and Grok video attempt from one structured brief.
                        </p>
                    </div>
                    <Link
                        href={route('generations.index')}
                        className="inline-flex min-h-11 items-center justify-center rounded-md border border-white/20 bg-white/[0.06] px-4 text-sm font-semibold text-white transition hover:border-[#34cf23]/50 hover:bg-[#34cf23]/10 focus:outline-none focus:ring-2 focus:ring-[#34cf23]"
                    >
                        History
                    </Link>
                </div>
            }
        >
            <Head title="Generate" />

            <div className="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:px-8">
                <section className="rounded-lg border border-white/10 bg-white/[0.07] shadow-2xl shadow-black/20 backdrop-blur-xl">
                    <div className="border-b border-white/10 px-6 py-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 className="text-xl font-black text-white">
                                    Creative brief
                                </h2>
                                <p className="mt-1 text-sm leading-6 text-white/60">
                                    Fill the inputs that shape the script, storyboard, and video prompt.
                                </p>
                            </div>
                            <span
                                className={`inline-flex rounded-full border px-3 py-1 text-xs font-bold ${
                                    grokConfigured
                                        ? 'border-[#34cf23]/30 bg-[#34cf23]/10 text-[#a8ff9f]'
                                        : 'border-amber-300/30 bg-amber-300/10 text-amber-100'
                                }`}
                            >
                                {grokConfigured ? 'Grok ready' : 'Fallback mode'}
                            </span>
                        </div>
                    </div>

                    {!grokConfigured && (
                        <div className="mx-6 mt-6 rounded-md border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm leading-6 text-amber-100">
                            Grok credentials are not configured. Vigen will still save a script and scene-by-scene storyboard.
                        </div>
                    )}

                    {flash?.success && (
                        <div className="mx-6 mt-6 rounded-md border border-[#34cf23]/30 bg-[#34cf23]/10 px-4 py-3 text-sm text-[#a8ff9f]">
                            {flash.success}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6 p-6">
                        <div>
                            <InputLabel value="Content template" />
                            <div className="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {templateOptions.map(([key, template]) => (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => applyTemplate(key)}
                                        className={`min-h-28 rounded-lg border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-[#34cf23] ${
                                            data.template_key === key
                                                ? 'border-[#34cf23]/70 bg-[#34cf23]/10 shadow-[0_0_24px_rgba(52,207,35,0.12)]'
                                                : 'border-white/10 bg-white/[0.045] hover:border-white/25 hover:bg-white/[0.07]'
                                        }`}
                                    >
                                        <span className="text-sm font-black text-white">
                                            {template.label}
                                        </span>
                                        <span className="mt-2 block text-xs leading-5 text-white/60">
                                            {template.structure}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <FieldShell>
                                <InputLabel htmlFor="video_type" value="Video type" />
                                <select
                                    id="video_type"
                                    value={data.video_type}
                                    onChange={(event) => setData('video_type', event.target.value)}
                                    className="min-h-11 w-full rounded-md border-white/10 bg-[#080b08] text-white shadow-sm focus:border-[#34cf23] focus:ring-[#34cf23]"
                                    required
                                >
                                    {videoTypes.map((type) => (
                                        <option key={type} value={type}>
                                            {type}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.video_type} />
                            </FieldShell>

                            <FieldShell>
                                <InputLabel htmlFor="tone" value="Preferred tone" />
                                <select
                                    id="tone"
                                    value={data.tone}
                                    onChange={(event) => setData('tone', event.target.value)}
                                    className="min-h-11 w-full rounded-md border-white/10 bg-[#080b08] text-white shadow-sm focus:border-[#34cf23] focus:ring-[#34cf23]"
                                    required
                                >
                                    {tones.map((tone) => (
                                        <option key={tone} value={tone}>
                                            {tone}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.tone} />
                            </FieldShell>
                        </div>

                        <FieldShell>
                            <InputLabel htmlFor="topic" value="Topic or idea" />
                            <textarea
                                id="topic"
                                value={data.topic}
                                onChange={(event) => setData('topic', event.target.value)}
                                className="min-h-32 w-full rounded-md border-white/10 bg-white/[0.06] text-white shadow-sm placeholder:text-white/40 focus:border-[#34cf23] focus:ring-[#34cf23]"
                                placeholder="Example: AI scheduling assistant for independent clinics"
                                required
                            />
                            <InputError message={errors.topic} />
                        </FieldShell>

                        <div className="grid gap-5 md:grid-cols-2">
                            <FieldShell>
                                <InputLabel htmlFor="keywords" value="Keywords to include" />
                                <TextInput
                                    id="keywords"
                                    value={data.keywords}
                                    onChange={(event) => setData('keywords', event.target.value)}
                                    className="block w-full"
                                    placeholder="launch, workflow, growth"
                                />
                                <InputError message={errors.keywords} />
                            </FieldShell>

                            <FieldShell>
                                <InputLabel htmlFor="target_audience" value="Target audience" />
                                <TextInput
                                    id="target_audience"
                                    value={data.target_audience}
                                    onChange={(event) => setData('target_audience', event.target.value)}
                                    className="block w-full"
                                    placeholder="clinic owners, course creators, founders"
                                    required
                                />
                                <InputError message={errors.target_audience} />
                            </FieldShell>
                        </div>

                        <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_280px]">
                            <fieldset>
                                <legend className="text-sm font-semibold text-white/80">
                                    Duration
                                </legend>
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    {[30, 60].map((duration) => (
                                        <label
                                            key={duration}
                                            className={`flex min-h-14 cursor-pointer items-center justify-between rounded-lg border px-4 text-sm font-bold transition ${
                                                Number(data.duration) === duration
                                                    ? 'border-[#34cf23]/70 bg-[#34cf23]/10 text-white'
                                                    : 'border-white/10 bg-white/[0.045] text-white/70 hover:border-white/25'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                className="sr-only"
                                                checked={Number(data.duration) === duration}
                                                onChange={() => setData('duration', duration)}
                                            />
                                            <span>{duration}s output</span>
                                            <span className="text-xs font-semibold text-white/50">
                                                merged clips
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.duration} className="mt-2" />
                            </fieldset>

                            <FieldShell>
                                <InputLabel htmlFor="aspect_ratio" value="Aspect ratio" />
                                <select
                                    id="aspect_ratio"
                                    value={data.aspect_ratio}
                                    onChange={(event) => setData('aspect_ratio', event.target.value)}
                                    className="min-h-11 w-full rounded-md border-white/10 bg-[#080b08] text-white shadow-sm focus:border-[#34cf23] focus:ring-[#34cf23]"
                                    required
                                >
                                    {aspectRatios.map((ratio) => (
                                        <option key={ratio} value={ratio}>
                                            {ratio}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.aspect_ratio} />
                            </FieldShell>
                        </div>

                        <div className="flex flex-wrap items-center gap-3 border-t border-white/10 pt-6">
                            <PrimaryButton disabled={processing}>
                                {processing ? 'Queueing...' : 'Generate output'}
                            </PrimaryButton>
                            <SecondaryButton type="button" disabled={processing} onClick={() => reset()}>
                                Reset
                            </SecondaryButton>
                            {processing && (
                                <span className="text-sm text-white/60">
                                    Saving the brief and opening the live status page.
                                </span>
                            )}
                        </div>
                    </form>
                </section>

                <aside className="space-y-6">
                    <section className="rounded-lg border border-white/10 bg-white/[0.06] p-5 shadow-xl shadow-black/20 backdrop-blur-xl">
                        <h2 className="text-base font-black text-white">
                            Generation path
                        </h2>
                        <div className="mt-4 space-y-3 text-sm leading-6 text-white/60">
                            <p>Compose a script and storyboard from the brief.</p>
                            <p>Queue Grok segment generation when credentials exist.</p>
                            <p>Merge generated clips and save the final video privately.</p>
                        </div>
                    </section>

                    <section className="rounded-lg border border-white/10 bg-white/[0.06] p-5 shadow-xl shadow-black/20 backdrop-blur-xl">
                        <div className="flex items-center justify-between">
                            <h2 className="text-base font-black text-white">
                                Recent
                            </h2>
                            <Link className="text-sm font-bold text-[#34cf23] hover:text-[#7dff72]" href={route('generations.index')}>
                                View all
                            </Link>
                        </div>
                        <div className="mt-4 space-y-3">
                            {recentGenerations.length === 0 ? (
                                <div className="rounded-lg border border-dashed border-white/20 p-4 text-sm leading-6 text-white/60">
                                    Generated videos and fallback storyboards will appear here.
                                </div>
                            ) : (
                                recentGenerations.map((generation) => (
                                    <Link
                                        key={generation.id}
                                        href={route('generations.show', generation.id)}
                                        className="block rounded-lg border border-white/10 bg-white/[0.045] p-3 transition hover:border-[#34cf23]/40 hover:bg-[#34cf23]/10"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <p className="line-clamp-2 text-sm font-bold text-white">
                                                {generation.topic}
                                            </p>
                                            <StatusBadge status={generation.status} />
                                        </div>
                                        <p className="mt-2 text-xs text-white/50">
                                            {generation.video_type} · {generation.created_at}
                                        </p>
                                    </Link>
                                ))
                            )}
                        </div>
                    </section>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}
