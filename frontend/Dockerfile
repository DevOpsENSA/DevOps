FROM node:20-alpine AS build

WORKDIR /frontend

COPY . .

RUN npm install
#installer les dependences js
RUN npm run build -- --configuration production
#compilers les fichiers angular les fichiers existent ici /frontend/dist/platform/browser
FROM nginx:alpine

RUN rm -rf /usr/share/nginx/html/* 

COPY --from=build /frontend/dist/platform/browser /usr/share/nginx/html/
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx","-g","daemon off;"]
#docker a seulement besoin de foreground pour qui ne s arrete pas le default c est qui il se lance en background