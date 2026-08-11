@props([
    'id',
    'label' => 'Informasi form',
])

<span class="form-info-popover" data-form-info-popover>
    <button
        type="button"
        class="form-info-popover__trigger"
        aria-label="{{ $label }}"
        aria-controls="{{ $id }}"
        aria-expanded="false"
    >
        <i class="fi fi-rr-info" aria-hidden="true"></i>
    </button>

    <span
        class="form-info-popover__panel"
        id="{{ $id }}"
        role="tooltip"
        aria-hidden="true"
    >
        <span class="form-info-popover__heading">
            <span class="form-info-popover__heading-icon" aria-hidden="true">
                <i class="fi fi-rr-info"></i>
            </span>
            <span>Panduan pengisian</span>
        </span>
        <span class="form-info-popover__body">{{ $slot }}</span>
    </span>
</span>

@once
    @push('styles')
        <style>
            .title-form {
                position: relative;
                min-width: 0;
            }

            .form-info-popover {
                display: inline-flex;
                flex: 0 0 auto;
                align-items: center;
            }

            .form-info-popover__trigger {
                display: inline-flex;
                width: 28px;
                height: 28px;
                padding: 0;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 50%;
                background: transparent;
                color: var(--blue-main);
                font: inherit;
                line-height: 1;
                cursor: help;
                transition: color .2s ease-out;
            }

            .form-info-popover__trigger i {
                position: relative;
                top: 1px;
                font-size: 13px;
            }

            .form-info-popover__trigger:hover,
            .form-info-popover.is-open .form-info-popover__trigger {
                color: var(--blue-hover);
            }

            .form-info-popover__trigger:focus-visible {
                outline: 2px solid var(--blue-main);
                outline-offset: 2px;
            }

            .form-info-popover__panel {
                position: absolute;
                z-index: 1200;
                top: calc(100% + 10px);
                left: 0;
                display: flex;
                visibility: hidden;
                width: min(390px, calc(100vw - 64px));
                padding: 14px;
                flex-direction: column;
                gap: 10px;
                border-radius: 12px;
                background: var(--white);
                box-shadow: 0 18px 44px var(--black-25);
                color: var(--dark-main);
                opacity: 0;
                pointer-events: none;
                transform: translateY(-5px);
                transition: opacity .2s cubic-bezier(.22, 1, .36, 1), transform .2s cubic-bezier(.22, 1, .36, 1), visibility .2s;
            }

            .form-info-popover.is-open .form-info-popover__panel {
                visibility: visible;
                opacity: 1;
                pointer-events: auto;
                transform: translateY(0);
            }

            .form-info-popover__heading {
                display: flex;
                align-items: center;
                gap: 9px;
                color: var(--dark-main);
                font-size: 12px;
                font-weight: 600;
                line-height: 1.35;
            }

            .form-info-popover__heading-icon {
                display: inline-flex;
                width: 26px;
                height: 26px;
                flex: 0 0 26px;
                align-items: center;
                justify-content: center;
                border-radius: 7px;
                background: var(--blue-main-10);
                color: var(--blue-main);
            }

            .form-info-popover__heading-icon i {
                position: relative;
                top: 1px;
            }

            .form-info-popover__body {
                color: var(--dark-secondary);
                font-size: 11px;
                font-weight: 400;
                line-height: 1.65;
                overflow-wrap: anywhere;
            }

            .form-info-popover__body strong {
                color: var(--dark-main);
                font-weight: 600;
            }

            @media (max-width: 640px) {
                .form-info-popover__trigger {
                    width: 30px;
                    height: 30px;
                }

                .form-info-popover__panel {
                    width: min(360px, calc(100vw - 48px));
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .form-info-popover__trigger,
                .form-info-popover__panel {
                    transition: none;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const popovers = Array.from(document.querySelectorAll('[data-form-info-popover]'));

                const setOpen = (popover, open, pinned = false) => {
                    const trigger = popover.querySelector('.form-info-popover__trigger');
                    const panel = popover.querySelector('.form-info-popover__panel');

                    popover.classList.toggle('is-open', open);
                    popover.dataset.pinned = open && pinned ? 'true' : 'false';
                    trigger?.setAttribute('aria-expanded', open ? 'true' : 'false');
                    panel?.setAttribute('aria-hidden', open ? 'false' : 'true');
                };

                const closeOthers = current => {
                    popovers.forEach(popover => {
                        if (popover !== current) setOpen(popover, false);
                    });
                };

                popovers.forEach(popover => {
                    const trigger = popover.querySelector('.form-info-popover__trigger');

                    popover.addEventListener('mouseenter', () => {
                        closeOthers(popover);
                        setOpen(popover, true, popover.dataset.pinned === 'true');
                    });

                    popover.addEventListener('mouseleave', () => {
                        if (popover.dataset.pinned !== 'true' && !popover.matches(':focus-within')) {
                            setOpen(popover, false);
                        }
                    });

                    popover.addEventListener('focusin', () => {
                        closeOthers(popover);
                        setOpen(popover, true, popover.dataset.pinned === 'true');
                    });

                    popover.addEventListener('focusout', () => {
                        window.setTimeout(() => {
                            if (popover.dataset.pinned !== 'true' && !popover.contains(document.activeElement)) {
                                setOpen(popover, false);
                            }
                        }, 0);
                    });

                    trigger?.addEventListener('click', event => {
                        event.stopPropagation();
                        const shouldOpen = popover.dataset.pinned !== 'true';
                        closeOthers(popover);
                        setOpen(popover, shouldOpen, shouldOpen);
                    });

                    trigger?.addEventListener('keydown', event => {
                        if (event.key !== 'Escape') return;
                        setOpen(popover, false);
                        trigger.focus();
                    });
                });

                document.addEventListener('click', event => {
                    popovers.forEach(popover => {
                        if (!popover.contains(event.target)) setOpen(popover, false);
                    });
                });
            });
        </script>
    @endpush
@endonce
