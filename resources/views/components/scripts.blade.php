@php
    $scriptUrl = config('livewire-sumsub.sdk.script_url');
    $containerId = config('livewire-sumsub.sdk.container_id', 'sumsub-websdk-container');
    $defaultLang = config('livewire-sumsub.sdk.default_lang', 'es');
@endphp

@push('scripts')
    <script src="{{ $scriptUrl }}"></script>
    <script>
        function sumsubWidget(initialToken) {
            return {
                sdkInstance: null,
                pendingRefreshResolve: null,
                containerSelector: '#{{ $containerId }}',
                defaultLang: @js($defaultLang),

                init() {
                    if (initialToken) {
                        this.$nextTick(() => this.launch(initialToken));
                    }
                },

                launch(token) {
                    if (this.sdkInstance) {
                        this.sdkInstance.destroy();
                        this.sdkInstance = null;
                    }

                    const component = this;

                    this.sdkInstance = snsWebSdk
                        .init(token, () => component.requestNewToken())
                        .withConf({
                            lang: document.documentElement.lang || this.defaultLang,
                            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                        })
                        .withOptions({ addViewportTag: false, adaptIframeHeight: true })
                        .on('idCheck.onStepCompleted', (payload) => {
                            console.debug('[Sumsub] step completed', payload);
                        })
                        .on('idCheck.applicantReviewComplete', (payload) => {
                            const answer = payload?.reviewResult?.reviewAnswer ?? 'pending';
                            Livewire.dispatch('sumsub:review-complete', { reviewAnswer: answer });
                        })
                        .on('idCheck.onError', (error) => {
                            console.error('[Sumsub] SDK error', error);
                        })
                        .build();

                    this.sdkInstance.launch(this.containerSelector);
                },

                requestNewToken() {
                    return new Promise((resolve) => {
                        this.pendingRefreshResolve = resolve;
                        Livewire.dispatch('sumsub:refresh-token');
                    });
                },

                resolveRefresh(token) {
                    if (this.pendingRefreshResolve) {
                        this.pendingRefreshResolve(token);
                        this.pendingRefreshResolve = null;
                    }
                },
            };
        }
    </script>
@endpush
