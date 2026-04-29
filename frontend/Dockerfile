FROM node:20-alpine AS build

WORKDIR /frontend

COPY . .

RUN npm install

RUN npm run build -- --configuration production


FROM nginx:alpine

RUN rm -rf /usr/share/nginx/html/* 

COPY --from=build /frontend/dist/platform/browser /usr/share/nginx/html/
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx","-g","daemon off;"]