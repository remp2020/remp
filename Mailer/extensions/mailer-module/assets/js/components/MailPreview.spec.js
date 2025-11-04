import { mount, createLocalVue } from '@vue/test-utils';
import { vi } from 'vitest';
import MailPreview from './MailPreview.vue';

describe('MailPreview.vue', () => {
    let mockProps;
    let mockWrapElement;

    beforeEach(() => {
        // Set up default props
        mockProps = {
            htmlLayout: '<html><body>{{ content|raw }}</body></html>',
            textLayout: 'Text Layout: {{ content|raw }}',
            htmlContent: '<h1>Test Email Content</h1>',
            textContent: 'Test Email Content',
        };

        // Mock wrap element
        mockWrapElement = {
            innerHTML: '',
            appendChild: vi.fn()
        };

        // Mock document.getElementById to return our mock element
        const originalGetElementById = document.getElementById;
        document.getElementById = vi.fn((id) => {
            if (id === 'previewFrameWrap') {
                return mockWrapElement;
            }
            return originalGetElementById.call(document, id);
        });

        // Mock window.document methods for iframe
        document.createElement = vi.fn((tag) => {
            if (tag === 'iframe') {
                const iframe = {
                    classList: {
                        add: vi.fn()
                    },
                    contentWindow: {
                        document: {
                            open: vi.fn(),
                            write: vi.fn(),
                            close: vi.fn(),
                            body: {
                                scrollHeight: 500,
                                style: {}
                            }
                        }
                    },
                    style: {}
                };
                return iframe;
            }
            return document.constructor.prototype.createElement.call(document, tag);
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
        // Clean up all jQuery event listeners to prevent cross-test interference
        $('body').off();
    });

    it('renders wrapper div', () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        expect(wrapper.find('#previewFrameWrap').exists()).toBe(true);
        expect(wrapper.find('#previewFrameWrap').classes()).toContain('previewFrameWrap');
    });

    it('creates iframe on mount', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.iframe).not.toBeNull();
        expect(document.createElement).toHaveBeenCalledWith('iframe');
    });

    it('renders HTML content in iframe on mount', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        expect(iframe.contentWindow.document.write).toHaveBeenCalled();
        expect(iframe.contentWindow.document.write).toHaveBeenCalledWith(
            expect.stringContaining('Test Email Content')
        );
    });

    it('merges content with HTML layout', async () => {
        const wrapper = mount(MailPreview, {
            propsData: {
                htmlLayout: '<html><head><title>Email</title></head><body>{{ content|raw }}</body></html>',
                htmlContent: '<p>Hello World</p>'
            }
        });

        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        const writtenContent = iframe.contentWindow.document.write.mock.calls[0][0];

        expect(writtenContent).toContain('<p>Hello World</p>');
        expect(writtenContent).toContain('<html>');
        expect(writtenContent).toContain('<body>');
    });

    it('handles content without layout', async () => {
        const wrapper = mount(MailPreview, {
            propsData: {
                htmlLayout: null,
                textLayout: null,
                htmlContent: '<p>Plain Content</p>',
                textContent: 'Plain Content'
            }
        });

        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        expect(iframe.contentWindow.document.write).toHaveBeenCalledWith('<p>Plain Content</p>');
    });

    it('strips Twig template tags from content', async () => {
        const wrapper = mount(MailPreview, {
            propsData: {
                htmlLayout: '{% if true %}{{ content|raw }}{% endif %}',
                htmlContent: '{% set var = "value" %}<p>Content</p>'
            }
        });

        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        const writtenContent = iframe.contentWindow.document.write.mock.calls[0][0];

        expect(writtenContent).not.toContain('{% if');
        expect(writtenContent).not.toContain('{% set');
        expect(writtenContent).not.toContain('{{ content|raw }}');
        expect(writtenContent).toContain('<p>Content</p>');
    });

    it('renders text layout when textLayout parameter is true', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        wrapper.vm.rerenderIframe(true);
        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        const writtenContent = iframe.contentWindow.document.write.mock.calls[1][0];

        expect(writtenContent).toContain('Text Layout');
    });

    it('applies white-space pre-wrap style for text layout', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        wrapper.vm.rerenderIframe(true);
        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        expect(iframe.contentWindow.document.body.style.whiteSpace).toBe('pre-wrap');
    });

    it('sets iframe height based on content', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick(); // Wait for height calculation

        const iframe = wrapper.vm.iframe;
        expect(iframe.style.height).toBe('600px'); // 500 + 100
    });

    it('listens for preview:change event on body', async () => {
        const onSpy = vi.spyOn($.fn, 'on');

        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        expect(onSpy).toHaveBeenCalled();
        const calls = onSpy.mock.calls;
        const previewChangeCall = calls.find(call => call[0] === 'preview:change');
        expect(previewChangeCall).toBeTruthy();
    });

    it('rerenders iframe when preview:change event is triggered', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        const rerenderSpy = vi.spyOn(wrapper.vm, 'rerenderIframe');

        // Trigger the preview:change event
        $('body').trigger('preview:change', { textarea: false });

        await wrapper.vm.$nextTick();

        expect(rerenderSpy).toHaveBeenCalled();
    });

    it('passes textarea parameter from preview:change event', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        const rerenderSpy = vi.spyOn(wrapper.vm, 'rerenderIframe');

        // Trigger with textarea: true
        $('body').trigger('preview:change', { textarea: true });

        await wrapper.vm.$nextTick();

        expect(rerenderSpy).toHaveBeenCalledWith(true);
    });

    it('reuses iframe on subsequent rerenders', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        const firstIframe = wrapper.vm.iframe;
        const createElementCalls = document.createElement.mock.calls.length;

        // Rerender
        wrapper.vm.rerenderIframe(false);
        await wrapper.vm.$nextTick();

        // Should not create a new iframe
        expect(document.createElement.mock.calls.length).toBe(createElementCalls);
        expect(wrapper.vm.iframe).toBe(firstIframe);
    });

    it('clears wrapper before appending iframe', async () => {
        // Set initial content
        mockWrapElement.innerHTML = 'existing content';

        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        expect(mockWrapElement.innerHTML).toBe('');
        expect(mockWrapElement.appendChild).toHaveBeenCalled();
    });

    it('adds previewFrame class to iframe', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.iframe.classList.add).toHaveBeenCalledWith('previewFrame');
    });

    it('updates preview when htmlContent prop changes', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        const rerenderSpy = vi.spyOn(wrapper.vm, 'rerenderIframe');

        // Change the prop
        await wrapper.setProps({
            htmlContent: '<h2>New Content</h2>'
        });

        expect(rerenderSpy).toHaveBeenCalled();
    });

    it('updates preview when htmlLayout prop changes', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        const rerenderSpy = vi.spyOn(wrapper.vm, 'rerenderIframe');

        // Change the prop
        await wrapper.setProps({
            htmlLayout: '<html><body>New Layout: {{ content|raw }}</body></html>'
        });

        expect(rerenderSpy).toHaveBeenCalled();
    });

    it('updates preview when textLayout prop changes', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        const rerenderSpy = vi.spyOn(wrapper.vm, 'rerenderIframe');

        // Change the prop
        await wrapper.setProps({
            textLayout: 'New Text Layout: {{ content|raw }}'
        });

        expect(rerenderSpy).toHaveBeenCalled();
    });

    it('opens iframe document before writing', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        expect(iframe.contentWindow.document.open).toHaveBeenCalledWith('text/html', 'replace');
    });

    it('closes iframe document after writing', async () => {
        const wrapper = mount(MailPreview, {
            propsData: mockProps
        });

        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        expect(iframe.contentWindow.document.close).toHaveBeenCalled();
    });

    it('handles empty htmlContent gracefully', async () => {
        const wrapper = mount(MailPreview, {
            propsData: {
                htmlLayout: '<html><body>{{ content|raw }}</body></html>',
                textLayout: null,
                htmlContent: ''
            }
        });

        await wrapper.vm.$nextTick();

        const iframe = wrapper.vm.iframe;
        expect(iframe.contentWindow.document.write).toHaveBeenCalled();
        // Should not crash
        expect(wrapper.vm.iframe).not.toBeNull();
    });
});
