import { router } from '@inertiajs/react';
import { ArrowUp } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';

export default function ScrollToTop() {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const scrollToTop = () => {
            window.scrollTo({
                top: 0,
                left: 0,
                behavior: 'smooth',
            });
        };

        const syncVisibility = () => {
            setIsVisible(window.scrollY > 320);
        };

        syncVisibility();

        const removeNavigateListener = router.on('navigate', scrollToTop);
        window.addEventListener('scroll', syncVisibility, { passive: true });

        return () => {
            removeNavigateListener();
            window.removeEventListener('scroll', syncVisibility);
        };
    }, []);

    return (
        <Button
            type="button"
            size="icon"
            onClick={() => {
                window.scrollTo({
                    top: 0,
                    left: 0,
                    behavior: 'smooth',
                });
            }}
            aria-label="Scroll to top"
            className={`fixed right-4 bottom-4 z-50 cursor-pointer rounded-full bg-primary text-primary-foreground shadow-lg transition-all duration-200 hover:bg-primary/90 sm:right-6 sm:bottom-6 ${
                isVisible ? 'pointer-events-auto translate-y-0 opacity-100' : 'pointer-events-none translate-y-3 opacity-0'
            }`}
        >
            <ArrowUp className="size-4" />
        </Button>
    );
}
