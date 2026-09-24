FROM golang:1.23-alpine AS build
WORKDIR /src
COPY go.mod ./
RUN go mod download
COPY . .
RUN CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o /out/numpo ./cmd/numpo
FROM alpine:3.21
RUN apk add --no-cache chromium && adduser -D -H numpo
USER numpo
COPY --from=build /out/numpo /usr/local/bin/numpo
EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/numpo"]
