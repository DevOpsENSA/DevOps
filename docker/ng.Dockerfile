# Stage 1: Build the Angular app
FROM node:20-alpine as build-stage

WORKDIR /app

# Install dependencies first (better caching)
COPY ../frontend/package*.json ./
RUN npm install

# Copy source and build
COPY ../frontend/ .
RUN npm run build -- --configuration=production

# Stage 2: Serve with Nginx
FROM nginx:stable-alpine as production-stage

# Copy the build output from the first stage
# Note: Angular 17 puts build in dist/[project-name]/browser
COPY --from=build-stage /app/dist/*/browser /usr/share/nginx/html

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
