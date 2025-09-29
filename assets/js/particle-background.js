/**
 * Particle Background Animation
 * Animated background with interactive particles
 */

class ParticleBackground {
    constructor() {
        this.canvas = null;
        this.ctx = null;
        this.particles = [];
        this.particleCount = 150; // Increased particle count
        this.mouse = {
            x: null,
            y: null,
            radius: 120 // Increased interaction radius
        };
        
        this.init();
    }

    init() {
        // Create canvas element
        this.createCanvas();
        
        // Set up event listeners
        this.setupEventListeners();
        
        // Initialize particles
        this.initParticles();
        
        // Start animation
        this.animate();
    }

    createCanvas() {
        // Create canvas element
        this.canvas = document.createElement('canvas');
        this.canvas.id = 'particle-background';
        this.canvas.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        `;
        
        // Add to body
        document.body.appendChild(this.canvas);
        
        // Get context
        this.ctx = this.canvas.getContext('2d');
        
        // Set initial size
        this.resizeCanvas();
    }

    setupEventListeners() {
        // Mouse move event
        window.addEventListener('mousemove', (e) => {
            this.mouse.x = e.clientX;
            this.mouse.y = e.clientY;
        });

        // Mouse leave event
        window.addEventListener('mouseleave', () => {
            this.mouse.x = null;
            this.mouse.y = null;
        });

        // Window resize event
        window.addEventListener('resize', () => {
            this.resizeCanvas();
            this.initParticles();
        });
    }

    resizeCanvas() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }

    initParticles() {
        this.particles = [];
        for (let i = 0; i < this.particleCount; i++) {
            this.particles.push(new Particle(this.canvas.width, this.canvas.height));
        }
    }

    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Update and draw particles
        for (let particle of this.particles) {
            particle.update(this.mouse);
            particle.draw(this.ctx);
        }
        
        // Draw connections between nearby particles
        this.drawConnections();
        
        requestAnimationFrame(() => this.animate());
    }

    drawConnections() {
        for (let i = 0; i < this.particles.length; i++) {
            for (let j = i + 1; j < this.particles.length; j++) {
                const dx = this.particles[i].x - this.particles[j].x;
                const dy = this.particles[i].y - this.particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < 100) {
                    const opacity = (100 - distance) / 100;
                    this.ctx.strokeStyle = `rgba(96, 165, 250, ${opacity * 0.3})`;
                    this.ctx.lineWidth = 1;
                    this.ctx.beginPath();
                    this.ctx.moveTo(this.particles[i].x, this.particles[i].y);
                    this.ctx.lineTo(this.particles[j].x, this.particles[j].y);
                    this.ctx.stroke();
                }
            }
        }
    }
}

class Particle {
    constructor(canvasWidth, canvasHeight) {
        this.x = Math.random() * canvasWidth;
        this.y = Math.random() * canvasHeight;
        this.size = Math.random() * 3 + 1;
        this.baseX = this.x;
        this.baseY = this.y;
        this.density = (Math.random() * 40) + 10;
        this.speedX = (Math.random() * 0.5) - 0.25;
        this.speedY = (Math.random() * 0.5) - 0.25;
        this.color = this.getRandomColor();
    }

    getRandomColor() {
        const colors = [
            'rgba(96, 165, 250, 0.8)',   // Blue
            'rgba(56, 189, 248, 0.7)',   // Sky blue
            'rgba(147, 197, 253, 0.6)',  // Light blue
            'rgba(59, 130, 246, 0.7)',   // Indigo
            'rgba(99, 102, 241, 0.6)'    // Violet
        ];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    update(mouse) {
        // Mouse interaction
        if (mouse.x && mouse.y) {
            const dx = mouse.x - this.x;
            const dy = mouse.y - this.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < mouse.radius) {
                const forceDirectionX = dx / distance;
                const forceDirectionY = dy / distance;
                const maxDistance = mouse.radius;
                const force = (maxDistance - distance) / maxDistance;
                const directionX = forceDirectionX * force * this.density;
                const directionY = forceDirectionY * force * this.density;
                
                this.x -= directionX;
                this.y -= directionY;
            } else {
                // Return to base position
                if (this.x !== this.baseX) {
                    const dx_base = this.x - this.baseX;
                    this.x -= dx_base / 15;
                }
                if (this.y !== this.baseY) {
                    const dy_base = this.y - this.baseY;
                    this.y -= dy_base / 15;
                }
            }
        } else {
            // Return to base position when mouse is not present
            if (this.x !== this.baseX) {
                const dx_base = this.x - this.baseX;
                this.x -= dx_base / 15;
            }
            if (this.y !== this.baseY) {
                const dy_base = this.y - this.baseY;
                this.y -= dy_base / 15;
            }
        }

        // Particle movement
        this.x += this.speedX;
        this.y += this.speedY;

        // Boundary collision
        if (this.x > window.innerWidth || this.x < 0) this.speedX *= -1;
        if (this.y > window.innerHeight || this.y < 0) this.speedY *= -1;

        // Keep particles within bounds
        if (this.x > window.innerWidth) this.x = window.innerWidth;
        if (this.x < 0) this.x = 0;
        if (this.y > window.innerHeight) this.y = window.innerHeight;
        if (this.y < 0) this.y = 0;
    }

    draw(ctx) {
        ctx.fillStyle = this.color;
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fill();
    }
}

// Initialize particle background when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ParticleBackground();
});
